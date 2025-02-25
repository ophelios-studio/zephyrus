<?php namespace Zephyrus\Network\Response;

use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Application\Views\PhpEngine;
use Zephyrus\Core\Application;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait RenderResponses
{
    /**
     * Renders the specified view with corresponding arguments using the configured rendering engine (from the
     * Application instance. By default, the LatteEngine is used.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function render(string $page, array $args = []): Response
    {
        $engine = Application::getInstance()->getRenderEngine();
        try {
            ob_start();
            $engine->renderFromFile($page, $args);
        } finally {
            $output = ob_get_clean();
        }
        Form::removeMemorizedValue();
        Flash::clearAll();
        Feedback::clear();
        $response = new Response(ContentType::HTML, 200);
        $response->setContent($output);
        return $response;
    }

    /**
     * Renders the specified PHP view with corresponding arguments.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function renderPhp(string $page, array $args = []): Response
    {
        try {
            ob_start();
            (new PhpEngine())->renderFromFile($page, $args);
        } finally {
            $output = ob_get_clean();
        }
        Form::removeMemorizedValue();
        Flash::clearAll();
        Feedback::clear();
        $response = new Response(ContentType::HTML, 200);
        $response->setContent($output);
        return $response;
    }

    /**
     * Renders the given data as HTML. Default behavior of any direct input.
     *
     * @param string $data
     * @return Response
     */
    public function html(string $data): Response
    {
        $response = new Response(ContentType::HTML, 200);
        $response->setContent($data);
        return $response;
    }
}
