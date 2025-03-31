<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Cache\ApcuCache;

class CacheTest extends TestCase
{
    public function testApcuAvailable()
    {
        // Make sure the "apc.enable_cli=on" directive is in the php.ini file. This cannot be set using the ini_set
        // function.
        self::assertTrue(ApcuCache::isAvailable());
    }

    public function testCacheValue()
    {
        $cache = new ApcuCache();
        $cache->set("TEST", "testing");
        self::assertEquals("testing", (string) apcu_fetch("TEST"));
    }

    public function testReadUncachedValue()
    {
        $cache = new ApcuCache();
        self::assertFalse($cache->has('BOB'));
        self::assertEquals(null, $cache->get('BOB'));
    }

    #[Depends("testCacheValue")]
    public function testReadCachedValue()
    {
        $cache = new ApcuCache();
        self::assertTrue($cache->has('TEST'));
        self::assertEquals("testing", $cache->get('TEST'));
    }

    #[Depends("testCacheValue")]
    public function testClearCachedValue()
    {
        $cache = new ApcuCache();
        $cache->delete('TEST');
        self::assertFalse($cache->has('TEST'));
        self::assertEquals(null, $cache->get('TEST'));
        self::assertFalse(apcu_fetch("TEST"));
    }
}
