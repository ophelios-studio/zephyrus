<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\ComposerPackage;

class ComposerPackageTest extends TestCase
{
    public function testGetPackages()
    {
        $array = ComposerPackage::getPackages();
        $versions = ComposerPackage::getVersions();
        $this->assertCount(5, $array);
        $this->assertEquals("v3.0.20", ComposerPackage::getVersion('latte/latte'));
        $this->assertEquals("v3.0.20", $versions['latte/latte']);
        $this->assertEquals("v3.0.20", $array['latte/latte']->version);
        $this->assertEquals("v3.0.20", ComposerPackage::getPackage('latte/latte')->version);
    }
}
