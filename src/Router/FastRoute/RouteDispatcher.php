<?php

declare(strict_types=1);

namespace Chubbyphp\Framework\Router\FastRoute;

use Chubbyphp\Framework\Router\RouteCollectionInterface;
use Chubbyphp\Framework\Router\RouteDispatcherException;
use Chubbyphp\Framework\Router\RouteDispatcherInterface;
use Chubbyphp\Framework\Router\RouteInterface;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Psr\Http\Message\ServerRequestInterface;

final class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * @var RouteInterface[]
     */
    private $routes;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @param RouteCollectionInterface $routeCollection
     */
    public function __construct(RouteCollectionInterface $routeCollection)
    {
        $this->routes = $routeCollection->getRoutes();

        $routeCollector = new RouteCollector(new RouteParser(), new DataGenerator());
        foreach ($this->routes as $route) {
            $routeCollector->addRoute($route->getMethod(), $route->getPattern(), $route->getName());
        }

        $this->dispatcher = new Dispatcher($routeCollector->getData());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return RouteInterface
     */
    public function dispatch(ServerRequestInterface $request): RouteInterface
    {
        $method = $request->getMethod();
        $path = rawurldecode($request->getUri()->getPath());

        $routeInfo = $this->dispatcher->dispatch($method, $path);

        if (Dispatcher::NOT_FOUND === $routeInfo[0]) {
            throw RouteDispatcherException::createForNotFound($request->getRequestTarget());
        }

        if (Dispatcher::METHOD_NOT_ALLOWED === $routeInfo[0]) {
            throw RouteDispatcherException::createForMethodNotAllowed(
                $method,
                $routeInfo[1],
                $request->getRequestTarget()
            );
        }

        /** @var RouteInterface $route */
        $route = $this->routes[$routeInfo[1]];
        $route = $route->withAttributes($routeInfo[2]);

        return $route;
    }
}
