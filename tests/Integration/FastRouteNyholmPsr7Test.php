<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Framework\Integration;

use Chubbyphp\Framework\Application;
use Chubbyphp\Framework\ExceptionHandler;
use Chubbyphp\Framework\Middleware\MiddlewareDispatcher;
use Chubbyphp\Framework\RequestHandler\CallbackRequestHandler;
use Chubbyphp\Framework\Router\FastRouteRouter;
use Chubbyphp\Framework\Router\Route;
use Chubbyphp\Framework\Router\RouteInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @coversNothing
 *
 * @internal
 */
final class FastRouteNyholmPsr7Test extends TestCase
{
    public function testOk(): void
    {
        $psr17Factory = new Psr17Factory();

        $route = Route::get('/hello/{name:[a-z]+}', 'hello', new CallbackRequestHandler(
            function (ServerRequestInterface $request) use ($psr17Factory) {
                $name = $request->getAttribute('name');
                $response = $psr17Factory->createResponse();
                $response->getBody()->write(sprintf('Hello, %s', $name));

                return $response;
            }
        ));

        $app = new Application(
            new FastRouteRouter([$route]),
            new MiddlewareDispatcher(),
            new ExceptionHandler($psr17Factory, true)
        );

        $request = new ServerRequest(
            RouteInterface::GET,
            '/hello/test'
        );

        $response = $app->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Hello, test', (string) $response->getBody());
    }

    public function testTestNotFound(): void
    {
        $psr17Factory = new Psr17Factory();

        $route = Route::get('/hello/{name:[a-z]+}', 'hello', new CallbackRequestHandler(
            function (ServerRequestInterface $request) use ($psr17Factory) {
                $name = $request->getAttribute('name');
                $response = $psr17Factory->createResponse();
                $response->getBody()->write(sprintf('Hello, %s', $name));

                return $response;
            }
        ));

        $app = new Application(
            new FastRouteRouter([$route]),
            new MiddlewareDispatcher(),
            new ExceptionHandler($psr17Factory, true)
        );

        $request = new ServerRequest(
            RouteInterface::GET,
            '/hello'
        );

        $response = $app->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString(
            'The page "/hello" you are looking for could not be found.',
            (string) $response->getBody()
        );
    }

    public function testMethodNotAllowed(): void
    {
        $psr17Factory = new Psr17Factory();

        $route = Route::get('/hello/{name:[a-z]+}', 'hello', new CallbackRequestHandler(
            function (ServerRequestInterface $request) use ($psr17Factory) {
                $name = $request->getAttribute('name');
                $response = $psr17Factory->createResponse();
                $response->getBody()->write(sprintf('Hello, %s', $name));

                return $response;
            }
        ));

        $app = new Application(
            new FastRouteRouter([$route]),
            new MiddlewareDispatcher(),
            new ExceptionHandler($psr17Factory, true)
        );

        $request = new ServerRequest(
            RouteInterface::POST,
            '/hello/test'
        );

        $response = $app->handle($request);

        self::assertSame(405, $response->getStatusCode());
        self::assertStringContainsString(
            'Method "POST" at path "/hello/test" is not allowed.',
            (string) $response->getBody()
        );
    }

    public function testException(): void
    {
        $psr17Factory = new Psr17Factory();

        $route = Route::get('/hello/{name:[a-z]+}', 'hello', new CallbackRequestHandler(
            function (): void {
                throw new \RuntimeException('Something went wrong');
            }
        ));

        $app = new Application(
            new FastRouteRouter([$route]),
            new MiddlewareDispatcher(),
            new ExceptionHandler($psr17Factory, true)
        );

        $request = new ServerRequest(
            RouteInterface::GET,
            '/hello/test'
        );

        $response = $app->handle($request);

        self::assertSame(500, $response->getStatusCode());

        $body = (string) $response->getBody();

        self::assertStringContainsString('RuntimeException', $body);
        self::assertStringContainsString('Something went wrong', $body);
    }
}