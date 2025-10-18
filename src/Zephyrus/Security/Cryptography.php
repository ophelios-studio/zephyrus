<?php namespace Zephyrus\Security;

use InvalidArgumentException;
use RuntimeException;
use Zephyrus\Application\Configuration;

class Cryptography
{
    /**
     * Version used in serialized AEAD payloads to allow future rotations.
     */
    private const PAYLOAD_VERSION = 1;

    /**
     * Default algorithm for legacy decrypt support (CBC + HMAC).
     * New encryptions use AEAD (Sodium XChaCha20-Poly1305 if available, otherwise OpenSSL AES-256-GCM).
     */
    private const DEFAULT_ENCRYPTION_ALGORITHM = 'aes-256-cbc';

    /**
     * Default algorithm to use with hashPassword() if none is specified otherwise within the security section of the
     * config.yml configuration file as property [password -> algorithm].
     */
    private const DEFAULT_PASSWORD_HASH_ALGORITHM = PASSWORD_DEFAULT;

    /**
     * Default cost option for BCRYPT if used (kept for backward compatibility with existing configs).
     */
    private const DEFAULT_PASSWORD_HASH_COST = 13;

    /**
     * Cryptographically hash a specified string. Adds optional pepper from config.
     *
     * @param string $clearTextPassword
     * @return string
     */
    public static function hashPassword(string $clearTextPassword): string
    {
        $config = Configuration::getSecurity("password");
        $pepper = $config['pepper'] ?? "";
        $algorithm = $config['algorithm'] ?? self::DEFAULT_PASSWORD_HASH_ALGORITHM;
        $options = $config['options'] ?? ['cost' => self::DEFAULT_PASSWORD_HASH_COST];

        if ($pepper !== "") {
            $clearTextPassword .= $pepper;
        }
        return password_hash($clearTextPassword, $algorithm, $options);
    }

    /**
     * Verify a hashed password. Adds optional pepper from config.
     *
     * @param string $clearTextPassword
     * @param string $hash
     * @return bool
     */
    public static function verifyHashedPassword(string $clearTextPassword, string $hash): bool
    {
        $config = Configuration::getSecurity("password");
        $pepper = $config['pepper'] ?? "";
        if ($pepper !== "") {
            $clearTextPassword .= $pepper;
        }
        return password_verify($clearTextPassword, $hash);
    }

    /**
     * Hash a string using a selected algorithm (defaults to sha256).
     *
     * @param string $string
     * @param string $algorithm
     * @return string
     */
    public static function hash(string $string, string $algorithm = 'sha256'): string
    {
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new InvalidArgumentException('Specified hashing algorithm not supported');
        }
        return hash($algorithm, $string);
    }

    /**
     * Hash a file using a selected algorithm (defaults to sha256).
     *
     * @param string $filename
     * @param string $algorithm
     * @return string
     */
    public static function hashFile(string $filename, string $algorithm = 'sha256'): string
    {
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new InvalidArgumentException('Specified hashing algorithm not supported');
        }
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Specified file to hash does not exist");
        }
        return hash_file($algorithm, $filename);
    }

    /**
     * Returns a random hex of desired length.
     *
     * @param int $length
     * @return string
     */
    public static function randomHex(int $length = 128): string
    {
        $bytes = (int)ceil($length / 2);
        return bin2hex(self::randomBytes($bytes));
    }

    /**
     * Returns a random integer between the provided min and max using a cryptographically secure generator.
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomInt(int $min, int $max): int
    {
        if ($max <= $min) {
            throw new InvalidArgumentException('Minimum equal or greater than maximum!');
        }
        if ($max < 0 || $min < 0) {
            throw new InvalidArgumentException('Only positive integers supported for now!');
        }
        return random_int($min, $max);
    }

    /**
     * Returns a random string from the specified character set. Defaults to [a-zA-Z0-9].
     *
     * @param int $length
     * @param string|array|null $characters
     * @return string
     */
    public static function randomString(int $length, string|array|null $characters = null): string
    {
        if (is_null($characters)) {
            $characters = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        }
        if (is_string($characters)) {
            $characters = str_split($characters);
        }
        $result = '';
        $characterCount = count($characters);
        for ($i = 0; $i < $length; ++$i) {
            $result .= $characters[self::randomInt(0, $characterCount - 1)];
        }
        return $result;
    }

    /**
     * Returns cryptographically secure random bytes.
     *
     * @param int $length
     * @return string
     */
    public static function randomBytes(int $length = 1): string
    {
        return random_bytes($length);
    }

    /**
     * Encrypt plaintext using AEAD. Prefers Sodium (XChaCha20-Poly1305); falls back to OpenSSL AES-256-GCM.
     * Output is a JSON string with fields: {v, alg, nonce|iv, ct, tag?}.
     *
     * @param string $plainText
     * @param string|null $key
     * @return string
     */
    public static function encrypt(string $plainText, ?string $key = null): string
    {
        $keyMaterial = $key ?? self::getEncryptionDefaultKey();
        if ($keyMaterial === null || $keyMaterial === '') {
            throw new RuntimeException("The encryption key cannot be null or empty. Provide a key or set a default key in the config.");
        }
        $aeadKey = self::normalizeKey($keyMaterial, 32);

        if (extension_loaded('sodium')) {
            $nonce = self::randomBytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
            $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plainText, '', $nonce, $aeadKey);
            return json_encode([
                'v' => self::PAYLOAD_VERSION,
                'alg' => 'xchacha20poly1305-ietf',
                'nonce' => base64_encode($nonce),
                'ct' => base64_encode($cipher)
            ], JSON_UNESCAPED_SLASHES);
        }

        $ivLen = openssl_cipher_iv_length('aes-256-gcm');
        $iv = self::randomBytes($ivLen);
        $tag = '';
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $aeadKey, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($cipher === false) {
            throw new RuntimeException('OpenSSL encryption failed');
        }
        return json_encode([
            'v' => self::PAYLOAD_VERSION,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'ct' => base64_encode($cipher),
            'tag' => base64_encode($tag)
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decrypt AEAD JSON payloads produced by encrypt(). Also supports legacy CBC+HMAC payloads.
     *
     * @param string $cipherText
     * @param string|null $key
     * @return string|null
     */
    public static function decrypt(string $cipherText, ?string $key = null): ?string
    {
        $keyMaterial = $key ?? self::getEncryptionDefaultKey();
        if ($keyMaterial === null || $keyMaterial === '') {
            throw new RuntimeException("The decryption key cannot be null or empty. Provide a key or set a default key in the config.");
        }
        $aeadKey = self::normalizeKey($keyMaterial, 32);

        // Try AEAD JSON payload first
        if (strlen($cipherText) > 0 && $cipherText[0] === '{') {
            $payload = json_decode($cipherText, true);
            if (!is_array($payload) || !isset($payload['alg'], $payload['v'])) {
                return null;
            }
            if ($payload['alg'] === 'xchacha20poly1305-ietf') {
                if (!extension_loaded('sodium')) {
                    return null;
                }
                $nonce = base64_decode($payload['nonce'] ?? '', true);
                $ct = base64_decode($payload['ct'] ?? '', true);
                if ($nonce === false || $ct === false) {
                    return null;
                }
                $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ct, '', $nonce, $aeadKey);
                return ($plain === false) ? null : $plain;
            }
            if ($payload['alg'] === 'aes-256-gcm') {
                $iv = base64_decode($payload['iv'] ?? '', true);
                $ct = base64_decode($payload['ct'] ?? '', true);
                $tag = base64_decode($payload['tag'] ?? '', true);
                if ($iv === false || $ct === false || $tag === false) {
                    return null;
                }
                $plain = openssl_decrypt($ct, 'aes-256-gcm', $aeadKey, OPENSSL_RAW_DATA, $iv, $tag, '');
                return ($plain === false) ? null : $plain;
            }
            return null;
        }

        // Legacy CBC + HMAC payload (base64(hmac_hex || iv || cipher))
        $raw = base64_decode($cipherText, true);
        if ($raw === false) {
            return null;
        }

        // Detect and parse legacy format
        if (strlen($raw) >= 64 + 1) {
            $algorithm = self::getEncryptionAlgorithm(); // likely 'aes-256-cbc'
            $ivLen = openssl_cipher_iv_length($algorithm);
            if (strlen($raw) >= 64 + $ivLen + 1) {
                $hmacHex = substr($raw, 0, 64);
                $iv = substr($raw, 64, $ivLen);
                $cipher = substr($raw, 64 + $ivLen);
                // Reproduce legacy key derivation (PBKDF2 hex slicing)
                $keys = self::deriveEncryptionKey($keyMaterial, $iv); // returns hex string length 64
                $encKey = substr($keys, 0, 32); // ASCII hex as used historically
                $authKey = substr($keys, 32);
                $hmacValidation = hash_hmac('sha256', $iv . $cipher, $authKey);
                if (!hash_equals($hmacHex, $hmacValidation)) {
                    return null;
                }
                $plain = openssl_decrypt($cipher, $algorithm, $encKey, OPENSSL_RAW_DATA, $iv);
                return ($plain === false) ? null : $plain;
            }
        }
        return null;
    }

    /**
     * Encrypt an entire file to destination (or overwrite source). Uses encrypt().
     *
     * @param string $plainTextFilename
     * @param string $key
     * @param string|null $destination
     */
    public static function encryptFile(string $plainTextFilename, string $key, ?string $destination = null): void
    {
        if (!file_exists($plainTextFilename)) {
            throw new InvalidArgumentException("Specified file to encrypt does not exist");
        }
        $originalContent = file_get_contents($plainTextFilename);
        $cipherText = self::encrypt($originalContent, $key);
        file_put_contents($destination ?? $plainTextFilename, $cipherText);
    }

    /**
     * Decrypt an entire file from source to destination (or overwrite source). Uses decrypt().
     *
     * @param string $cipherTextFilename
     * @param string $key
     * @param string|null $destination
     */
    public static function decryptFile(string $cipherTextFilename, string $key, ?string $destination = null): void
    {
        if (!file_exists($cipherTextFilename)) {
            throw new InvalidArgumentException("Specified file to decrypt does not exist");
        }
        $cipherText = file_get_contents($cipherTextFilename);
        $originalContent = self::decrypt($cipherText, $key);
        if ($originalContent === null) {
            throw new RuntimeException('Decryption failed for the specified file');
        }
        file_put_contents($destination ?? $cipherTextFilename, $originalContent);
    }

    /**
     * Legacy PBKDF2-based key derivation retained for backward compatibility.
     * Returns a hex string by default (as historically used by the project).
     *
     * @param string $password
     * @param string $salt
     * @param int $length
     * @param int $iteration
     * @return string
     */
    public static function deriveEncryptionKey(string $password, string $salt, int $length = 64, int $iteration = 80000): string
    {
        return hash_pbkdf2('sha256', $password, $salt, $iteration, $length);
    }

    /**
     * Returns the configured baseline legacy encryption algorithm for backward compatibility.
     *
     * @return string
     */
    public static function getEncryptionAlgorithm(): string
    {
        $config = Configuration::getSecurity("encryption");
        return $config['algorithm'] ?? self::DEFAULT_ENCRYPTION_ALGORITHM;
    }

    /**
     * Returns the configured default encryption key to be used in the application with encrypt and decrypt methods.
     *
     * @return string|null
     */
    public static function getEncryptionDefaultKey(): ?string
    {
        $config = Configuration::getSecurity("encryption");
        return $config['key'] ?? null;
    }

    /**
     * Normalize any provided key material to a fixed-size binary key.
     * Uses SHA-256 KDF to derive 32 bytes suitable for AEAD keys.
     *
     * @param string $keyMaterial
     * @param int $length
     * @return string
     */
    private static function normalizeKey(string $keyMaterial, int $length = 32): string
    {
        $derived = hash('sha256', $keyMaterial, true);
        if ($length <= 32) {
            return substr($derived, 0, $length);
        }
        // Expand with HKDF if larger is ever needed
        return hash_hkdf('sha256', $keyMaterial, $length, 'zephyrus-crypto', '');
    }
}
