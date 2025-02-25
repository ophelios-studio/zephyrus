<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Localization;
use Zephyrus\Application\LocalizationConfiguration;
use Zephyrus\Core\Application;
use Zephyrus\Exceptions\LocalizationException;

class LocalizationTest extends TestCase
{
    public function testLocalize()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);
        copy(ROOT_DIR . '/locale/routes.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        $localization->start();
        self::assertEquals('fr_CA', $localization->getLocale());
        self::assertEquals('fr_CA', $localization->getLoadedLanguage()->locale);
        self::assertEquals('America/Montreal', date_default_timezone_get());
        self::assertEquals("Le courriel est invalide", $localization->localize("messages.errors.invalid_email"));
        self::assertEquals("Email is invalid", $localization->localize("en_CA.messages.errors.invalid_email"));
        self::assertEquals("L'utilisateur [bob] a été ajouté avec succès", $localization->localize("messages.success.add_user", ["bob"]));
        self::assertEquals("not.found", $localization->localize("not.found"));
        self::assertEquals("messages.success.bob", $localization->localize("messages.success.bob"));
        self::assertEquals("/connexion", $localization->localize("routes.login"));
        self::assertEquals("/admin", $localization->localize("routes.administration")); // subfolder test
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", localize("messages.success.add_user", "martin"));
        self::assertEquals("L'utilisateur [martin] a été ajouté avec succès", localize("L'utilisateur [%s] a été ajouté avec succès", 'martin'));
        self::assertEquals("/no/key/3", $localization->localize("/no/%s/{id}", ['id' => 3, 'key']));

        // Rest should be english
        $localization->changeLanguage(new LocalizationConfiguration([
            'locale' => 'en_CA',
        ]));
        self::assertEquals("Email is invalid", $localization->localize("messages.errors.invalid_email"));
        self::assertEquals("Password does not respect established constraints", $localization->localize("messages.errors.invalid_password"));

        // Back to normal
        $localization->changeLanguage($configuration);
        self::assertEquals("Le courriel est invalide", $localization->localize("messages.errors.invalid_email"));
        self::assertEquals("Le mot de passe ne respecte pas les contraintes", $localization->localize("messages.errors.invalid_password"));

        $reservedKeywords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'clone',
            'const', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare',
            'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally',
            'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
            'protected', 'public', 'require', 'require_once', 'return', 'static', 'throw', 'trait', 'try',
            'unset', 'use', 'var', 'while', 'xor', 'yield'
        ];
        foreach ($reservedKeywords as $w) {
            self::assertEquals('a', localize("test.$w"));
        }
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
    }

    public function testErrorInJson()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);
        copy(ROOT_DIR . '/locale/broken.json', ROOT_DIR . '/locale/fr_CA/broken.json');
        try {
            $localization->generate(true);
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
            self::assertEquals("broken.json", basename($e->getJsonFile()));
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken.json');
    }

    public function testError2InJson()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);
        copy(ROOT_DIR . '/locale/broken2.json', ROOT_DIR . '/locale/fr_CA/broken2.json');
        try {
            $localization->generate(true);
        } catch (LocalizationException $e) {
            self::assertEquals(JSON_ERROR_SYNTAX, $e->getCode());
        }
        unlink(ROOT_DIR . '/locale/fr_CA/broken2.json');
    }

    public function testCacheOutdated()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);

        $localization->clearCache();
        copy(ROOT_DIR . '/locale/routes.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        $localization->start();
        self::assertEquals("/connexion", $localization->localize("routes.login"));
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');

        // Simulate json changes
        copy(ROOT_DIR . '/locale/routes2.json', ROOT_DIR . '/locale/fr_CA/routes.json');
        $localization->generate(true);
        self::assertEquals("/connexion2", $localization->localize("routes.login"));
        $localization->clearCache();
        unlink(ROOT_DIR . '/locale/fr_CA/routes.json');
    }

    public function testInvalidLocalization()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);
        // Shall not break
        $result = $localization->localize('bob/3');
        self::assertEquals('bob/3', $result);
    }

    public function testInstalledLanguages()
    {
        $configuration = new LocalizationConfiguration(Configuration::getLocale());
        $localization = new Localization($configuration);
        $languages = $localization->getInstalledLanguages();
        self::assertCount(2, $languages);
        self::assertEquals('en', $languages['en_CA']->lang_code);
        self::assertEquals('CA', $languages['en_CA']->country_code);
        self::assertEquals('fr', $languages['fr_CA']->lang_code);
    }
}
