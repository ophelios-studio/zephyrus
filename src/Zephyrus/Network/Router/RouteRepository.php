<?php namespace Zephyrus\Network\Router;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;

class RouteRepository
{
    /**
     * Associative array that contains all defined routes. Routes are organized by HTTP method as main key, value is an
     * array of stdClass representing the route.
     *
     * @var array
     */
    private array $routes = [];

    /**
     * APCu php cache to keep a reference to the controller repository.
     *
     * @var RouteCache
     */
    private RouteCache $cache;

    public function __construct()
    {
        $this->cache = new RouteCache();
    }

    /**
     * Verifies if the currently cached route definitions (if it exists) are outdated and need regeneration. Returns
     * true if the given time is newer than the cache creation time or if no cache exist.
     *
     * @param int $time
     * @return bool
     */
    public function isCacheOutdated(int $time): bool
    {
        return $this->cache->isOutdated($time);
    }

    /**
     * Saves the instance currently defined routes into the APCu PHP cache. To optimize futur calls, the method
     * initializeFromCache() should be called.
     *
     * @return void
     */
    public function cache(): void
    {
        $this->cache->cache($this->routes);
    }

    public function clear(): void
    {
        $this->cache->clear();
        $this->routes = [];
    }

    /**
     * Initializes the application route definitions from the APCu PHP cache and thus avoiding unnecessary looping
     * through the various project controllers.
     *
     * @return void
     */
    public function initializeFromCache(): void
    {
        $this->routes = $this->cache->read() ?? [];
    }

    /**
     * @param HttpMethod|null $method
     * @return RouteDefinition[]
     */
    public function getRoutes(?HttpMethod $method = null): array
    {
        if ($method) {
            if (array_key_exists($method->value, $this->routes)) {
                return $this->routes[$method->value];
            }
            return [];
        }
        return $this->routes;
    }

    /**
     * @param HttpMethod $method
     * @param string $requestedUri
     * @return RouteDefinition[]
     */
    public function findRoutes(HttpMethod $method, string $requestedUri): array
    {
        $routes = $this->getRoutes($method);
        $matchingRoutes = [];
        foreach ($routes as $route) {
            if ($route->matchUrl($requestedUri)) {
                $matchingRoutes[] = $route;
            }
        }
        if (!empty($matchingRoutes)) {
            usort($matchingRoutes, function (RouteDefinition $a, RouteDefinition $b) {
                return strcmp($a->getRoute(), $b->getRoute());
            });
        }
        return $matchingRoutes;
    }

    /**
     * Adds a new GET route for the application. The GET method must be used to represent a specific resource (or
     * collection) in some representational format (HTML, JSON, XML, ...). Normally, a GET request must only present
     * data and not alter them in any way.
     *
     * E.g. GET /books
     *      GET /book/{id}
     *
     * @param string $url
     * @param callable | array $callback
     * @param array $acceptedFormats
     * @param array $authorizationRules
     */
    public function get(string $url, callable|array $callback, array $acceptedFormats = [ContentType::ANY], array $authorizationRules = []): void
    {
        $route = new RouteDefinition($url);
        $route->setCallback($callback);
        $route->setAcceptedContentTypes($acceptedFormats);
        $route->setAuthorizationRules($authorizationRules);
        $this->addRoute(HttpMethod::GET, $route);
    }

    /**
     * Adds a new POST route for the application. The POST method must be used to create a new entry in a collection. It
     * is rarely used on a specific resource.
     *
     * E.g. POST /books
     *
     * @param string $url
     * @param callable | array $callback
     * @param array $acceptedFormats
     * @param array $authorizationRules
     */
    public function post(string $url, callable|array $callback, array $acceptedFormats = [ContentType::ANY], array $authorizationRules = []): void
    {
        $route = new RouteDefinition($url);
        $route->setCallback($callback);
        $route->setAcceptedContentTypes($acceptedFormats);
        $route->setAuthorizationRules($authorizationRules);
        $this->addRoute(HttpMethod::POST, $route);
    }

    /**
     * Adds a new PUT route for the application. The PUT method must be used to update a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. PUT /book/{id}
     *
     * @param string $url
     * @param callable | array $callback
     * @param array $acceptedFormats
     * @param array $authorizationRules
     */
    public function put(string $url, callable|array $callback, array $acceptedFormats = [ContentType::ANY], array $authorizationRules = []): void
    {
        $route = new RouteDefinition($url);
        $route->setCallback($callback);
        $route->setAcceptedContentTypes($acceptedFormats);
        $route->setAuthorizationRules($authorizationRules);
        $this->addRoute(HttpMethod::PUT, $route);
    }

    /**
     * Adds a new PATCH route for the application. The PATCH method must be used to update a specific resource or
     * collection and must be considered idempotent. Should be used instead of PUT when it is possible to update only
     * given fields to update and not the entire resource.
     *
     * E.g. PATCH /book/{id}
     *
     * @param string $url
     * @param callable | array $callback
     * @param array $acceptedFormats
     * @param array $authorizationRules
     */
    public function patch(string $url, callable|array $callback, array $acceptedFormats = [ContentType::ANY], array $authorizationRules = []): void
    {
        $route = new RouteDefinition($url);
        $route->setCallback($callback);
        $route->setAcceptedContentTypes($acceptedFormats);
        $route->setAuthorizationRules($authorizationRules);
        $this->addRoute(HttpMethod::PATCH, $route);
    }

    /**
     * Adds a new DELETE route for the application. The DELETE method must be used only to delete a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. DELETE /book/{id}
     *      DELETE /books
     *
     * @param string $url
     * @param callable | array $callback
     * @param array $acceptedFormats
     * @param array $authorizationRules
     */
    public function delete(string $url, callable|array $callback, array $acceptedFormats = [ContentType::ANY], array $authorizationRules = []): void
    {
        $route = new RouteDefinition($url);
        $route->setCallback($callback);
        $route->setAcceptedContentTypes($acceptedFormats);
        $route->setAuthorizationRules($authorizationRules);
        $this->addRoute(HttpMethod::DELETE, $route);
    }

    /**
     * Adds a new route for the application. Make sure to create the adequate structure with corresponding parameters
     * regex pattern if needed. If the callback comes from a controller, it must be given as an array form [instance,
     * 'methodName'].
     *
     * @param HttpMethod $method
     * @param RouteDefinition $route
     */
    public function addRoute(HttpMethod $method, RouteDefinition $route): void
    {
        $this->routes[$method->value][$route->getRoute()] = $route;
    }
}
