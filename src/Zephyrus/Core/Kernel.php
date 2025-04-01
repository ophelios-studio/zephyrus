<?php namespace Zephyrus\Core;

use Dotenv\Dotenv;
use Tracy\Debugger;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Exceptions\Security\InvalidCsrfException;
use Zephyrus\Exceptions\Security\MissingCsrfException;
use Zephyrus\Exceptions\Security\UnauthorizedAccessException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\ServerEnvironnement;
use Zephyrus\Utilities\FileSystem\Directory;

class Kernel
{
    private ServerEnvironnement $serverEnvironnement;
    private Request $request;

    public function __construct()
    {
        $this->initializeEnvironnement();
        require_once(Bootstrap::getHelperFunctionsPath());
        if (Configuration::getApplication('debug', false)) {
            Debugger::enable(Debugger::Development);
            if (!Directory::exists(ROOT_DIR . '/temp')) {
                Directory::create(ROOT_DIR . '/temp');
            }
            Debugger::$logDirectory = ROOT_DIR . '/temp';
        }
        $this->serverEnvironnement = new ServerEnvironnement($_SERVER);
        $this->request = new Request($this->serverEnvironnement);
    }

    public function run(Router $router): Response
    {
        try {
            return $router->resolve($this->request);
        } catch (RouteMethodUnsupportedException $e) {
            return $this->handleUnsupportedMethod($e);
        } catch (RouteNotAcceptedException $e) {
            return $this->handleUnacceptedRoute($e);
        } catch (RouteNotFoundException $e) {
            return $this->handleRouteNotFound($e);
        } catch (IntrusionDetectionException $e) {
            return $this->handleDetectedIntrusion($e);
        } catch (InvalidCsrfException $e) {
            return $this->handleInvalidCsrf($e);
        } catch (UnauthorizedAccessException $e) {
            return $this->handleUnauthorizedException($e);
        } catch (RouteArgumentException $e) {
            return $this->handleRouteArgumentException($e);
        } catch (MissingCsrfException $e) {
            return $this->handleMissingCsrf($e);
        }
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getServerEnvironnement(): ServerEnvironnement
    {
        return $this->serverEnvironnement;
    }

    /**
     * Default handling for unauthorized access exception. Meaning the current user is not authorized to view a specific
     * route or do a specific action. Can be overridden if needed for a more specific behavior.
     *
     * @param UnauthorizedAccessException $exception
     * @return Response|null
     */
    protected function handleUnauthorizedException(UnauthorizedAccessException $exception): ?Response
    {
        return Response::builder()->abortUnauthorized($exception->getMessage());
    }

    protected function handleRouteArgumentException(RouteArgumentException $exception): ?Response
    {
        return Response::builder()->abortBadRequest($exception->getMessage());
    }

    /**
     * Default handling of intrusion detection. This method is triggered when the IDS is active and detects an intrusion
     * equals or greater than the configured impact_threshold (default to 0, meaning any detection is registered). Can
     * be overridden if needed for a more specific behavior.
     *
     * @param IntrusionDetectionException $exception
     * @return Response|null
     */
    protected function handleDetectedIntrusion(IntrusionDetectionException $exception): ?Response
    {
        return Response::builder()->abortForbidden($exception->getMessage());
    }

    /**
     * Defines the actions to take when an invalid CSRF exception occurs in the system. Meaning that a form was not
     * properly sent or used. If the method returns a Response, it will end the processing of the route immediately and
     * give this response back to the client. Can be overridden if needed for a more specific behavior.
     *
     * @param InvalidCsrfException $exception
     * @return Response|null
     */
    protected function handleInvalidCsrf(InvalidCsrfException $exception): ?Response
    {
        return Response::builder()->abortForbidden($exception->getMessage());
    }

    protected function handleMissingCsrf(MissingCsrfException $exception): ?Response
    {
        return Response::builder()->abortForbidden($exception->getMessage());
    }

    protected function handleRouteNotFound(RouteNotFoundException $exception): ?Response
    {
        return Response::builder()->abortNotFound($exception->getMessage());
    }

    protected function handleUnsupportedMethod(RouteMethodUnsupportedException $exception): ?Response
    {
        return Response::builder()->abortMethodNotAllowed($exception->getMessage());
    }

    protected function handleUnacceptedRoute(RouteNotAcceptedException $exception): ?Response
    {
        return Response::builder()->abortNotAcceptable($exception->getMessage());
    }

    /**
     * Loads the configurations within .env file into the $_ENV super global. Seeks the .env file at the root directory
     * defined with the « ROOT_DIR » constant. Creates CONSTANTS automatically with the env variables. Ignores if the
     * .env does not exist (some project could not have a use for it).
     */
    private function initializeEnvironnement(): void
    {
        if (!file_exists(ROOT_DIR . '/.env')) {
            return;
        }
        $dotenv = Dotenv::createImmutable(ROOT_DIR);
        $env = $dotenv->load();
        foreach ($env as $item => $value) {
            define($item, $value);
        }
    }
}
