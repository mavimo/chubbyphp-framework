<?php

declare(strict_types=1);

namespace Chubbyphp\Framework\Router\FastRoute;

use Chubbyphp\Framework\Router\RouteCollectionException;
use Chubbyphp\Framework\Router\RouteCollectionInterface;
use Chubbyphp\Framework\Router\RouteInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Chubbyphp\Framework\Router\Route;

final class RouteCollection implements RouteCollectionInterface
{
    /**
     * @var RouteInterface[]
     */
    private $routes = [];

    /**
     * @var string[]
     */
    private $patternStack = [];

    /**
     * @var array[]
     */
    private $middlewaresStack = [];

    /**
     * @var bool
     */
    private $freeze = false;

    /**
     * @param string $pattern
     * @param array  $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function group(string $pattern, array $middlewares = []): self
    {
        if ($this->freeze) {
            throw RouteCollectionException::createFreezeException();
        }

        $this->validateMiddlewares(__METHOD__, $middlewares);

        $this->patternStack[] = $pattern;
        $this->middlewaresStack[] = $middlewares;

        return $this;
    }

    /**
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function end(): self
    {
        if ($this->freeze) {
            throw RouteCollectionException::createFreezeException();
        }

        array_pop($this->patternStack);
        array_pop($this->middlewaresStack);

        return $this;
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function delete(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::DELETE, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function get(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::GET, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function head(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::HEAD, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function options(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::OPTIONS, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function patch(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::PATCH, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function post(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::POST, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    public function put(
        string $pattern,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        return $this->route($pattern, RouteInterface::PUT, $name, $requestHandler, $middlewares);
    }

    /**
     * @param string                  $pattern
     * @param string                  $method
     * @param string                  $name
     * @param RequestHandlerInterface $requestHandler
     * @param array                   $middlewares
     *
     * @return self
     *
     * @throws RouteCollectionException
     */
    private function route(
        string $pattern,
        string $method,
        string $name,
        RequestHandlerInterface $requestHandler,
        array $middlewares = []
    ): self {
        if ($this->freeze) {
            throw RouteCollectionException::createFreezeException();
        }

        $this->validateMiddlewares(__METHOD__, $middlewares);

        $this->routes[$name] = new Route(
            $this->getPattern($pattern),
            [],
            $method,
            $name,
            $requestHandler,
            $this->getMiddlewares($middlewares)
        );

        return $this;
    }

    /**
     * @param string                $method
     * @param MiddlewareInterface[] $middlewares
     */
    private function validateMiddlewares(string $method, array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new \TypeError(
                    sprintf(
                        '%s expects parameter 1 to be %s[], %s[] given',
                        $method,
                        MiddlewareInterface::class,
                        is_object($middleware) ? get_class($middleware) : gettype($middleware)
                    )
                );
            }
        }
    }

    /**
     * @param string $pattern
     *
     * @return string
     */
    private function getPattern(string $pattern): string
    {
        return implode('', $this->patternStack).$pattern;
    }

    /**
     * @param MiddlewareInterface[] $routeMiddlewares
     *
     * @return MiddlewareInterface[]
     */
    private function getMiddlewares(array $routeMiddlewares): array
    {
        $middlewares = [];
        foreach ($this->middlewaresStack as $middlewaresFromStack) {
            $middlewares = array_merge($middlewares, $middlewaresFromStack);
        }

        return array_merge($middlewares, $routeMiddlewares);
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        $this->freeze = true;

        return $this->routes;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $string = '';
        foreach ($this->getRoutes() as $route) {
            $string .= $route.PHP_EOL;
        }

        return trim($string);
    }
}