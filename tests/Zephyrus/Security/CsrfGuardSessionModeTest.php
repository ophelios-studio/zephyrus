<?php namespace Zephyrus\Tests\Security;

use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Configuration\Security\CsrfConfiguration;
use Zephyrus\Security\CsrfGuard;
use Zephyrus\Tests\RequestUtility;

class CsrfGuardSessionModeTest extends TestCase
{
    public function testSessionModeGeneration()
    {
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req, new CsrfConfiguration([
            'mode' => CsrfConfiguration::MODE_SESSION
        ]));

        $result1 = $csrf->generateHiddenFields();
        $result2 = $csrf->generateHiddenFields();

        $this->assertEquals($result1, $result2, "Tokens should be identical in session mode");
        $this->assertTrue($this->hasHiddenFields($result1));
    }

    private function hasHiddenFields($html): bool
    {
        return (bool)preg_match("/<input type=\"hidden\" name=\"CSRFToken\" value=\"CSRFGuard_Session\\$[0-9a-zA-Z]+\" \/>/", $html);
    }

    public function testSessionModeValidation()
    {
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req, new CsrfConfiguration([
            'mode' => CsrfConfiguration::MODE_SESSION,
            'guard_methods' => ['DELETE']
        ]));

        // Generate token
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        // First validation
        $req = RequestUtility::delete("/test", 'CSRFToken=' . $name . '$' . $value);
        $csrf = new CsrfGuard($req, new CsrfConfiguration([
            'mode' => CsrfConfiguration::MODE_SESSION,
            'guard_methods' => ['DELETE']
        ]));
        $csrf->run();

        // Second validation (should still work as token is not removed)
        $req = RequestUtility::delete("/test", 'CSRFToken=' . $name . '$' . $value);
        $csrf = new CsrfGuard($req, new CsrfConfiguration([
            'mode' => CsrfConfiguration::MODE_SESSION,
            'guard_methods' => ['DELETE']
        ]));
        $csrf->run();

        $this->assertTrue(true); // Should reach here without exception
    }

    private function getHiddenFieldValues($html): array
    {
        $output = [];
        preg_match("/<input type=\"hidden\" name=\"CSRFToken\" value=\"(CSRFGuard_Session)\\$([0-9a-zA-Z]+)\" \/>/", $html, $output);
        return $output;
    }
}
