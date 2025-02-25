<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Core\Application;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\ServerEnvironnement;

class ControllerRenderTest extends TestCase
{
    public function testRender()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::render('test', ['item' => (object) ['name' => 'Bob Lewis', 'price' => 12.30]]);
            }
        };
        self::assertEquals('<p>Bob Lewis</p>', $controller->index()->getContent());
    }

    public function testRenderPhp()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::renderPhp('test2', ['a' => 'allo']);
            }
        };
        self::assertEquals('<h1>allo</h1>', $controller->index()->getContent());
    }

    public function testRenderUnavailablePhp()
    {
        $this->initiateApplication();
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage("The specified view file [dfgdfgdfg] is not available (not readable or does not exists)");
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::renderPhp('dfgdfgdfg', ['a' => 'allo']);
            }
        };
        $controller->index();
    }

    public function testRenderUnavailableLatte()
    {
        $this->initiateApplication();
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage("The specified view file [dfgdfgdfg] is not available (not readable or does not exists)");
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::render('dfgdfgdfg', ['a' => 'allo']);
            }
        };
        $controller->index();
    }

    public function testRenderPhpWithFlashAndFeedback()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::register(["email" => ["test"]]);
                return parent::renderPhp('test3', [
                    'flash' => Flash::readAll(),
                    'feedback' => Feedback::readAll()
                ]);
            }
        };
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $controller->index()->getContent());
    }

    public function testRenderLatteWithFlashAndFeedback()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::register(["email" => ["test"]]);
                return parent::render('test4', [
                    "flash" => Flash::readAll(),
                    "feedback" => Feedback::readAll()
                ]);
            }
        };
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $controller->index()->getContent());
    }

    public function testJson()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::json(['test' => ['a' => 2, 'b' => 'bob']]);
            }
        };
        self::assertEquals('{"test":{"a":2,"b":"bob"}}', $controller->index()->getContent());
    }

    public function testHtml()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::html("<html>test</html>");
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<html>test</html>', $output);
    }

    public function testPlain()
    {
        $this->initiateApplication();
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::plain("test plain");
            }
        };
        ob_start();
        $controller->index()->send();
        $headers = xdebug_get_headers();
        $output = ob_get_clean();
        self::assertEquals('test plain', $output);
        self::assertTrue(in_array('Content-Type: text/plain;charset=UTF-8', $headers));
    }

    private function initiateApplication(): void
    {
        $server['REQUEST_METHOD'] = 'GET';
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['REQUEST_URI'] = '/test?id=yeah';
        $server['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $server['HTTP_HOST'] = 'test.local';
        $server['SERVER_PORT'] = '80';
        $server['CONTENT_TYPE'] = ContentType::PLAIN;

        $env = new ServerEnvironnement($server);
        $req = new Request($env);
        Application::initiate($req);
    }
}