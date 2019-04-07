<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Framework\Router\FastRoute;

use Chubbyphp\Framework\Router\FastRoute\UrlGenerator;
use Chubbyphp\Framework\Router\RouteCollectionInterface;
use Chubbyphp\Framework\Router\RouteInterface;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use FastRoute\RouteParser;
use Chubbyphp\Framework\Router\UrlGeneratorException;

/**
 * @covers \Chubbyphp\Framework\Router\FastRoute\UrlGenerator
 */
final class UrlGeneratorTest extends TestCase
{
    use MockByCallsTrait;

    public function testRequestTargetWithMissingRoute(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->expectExceptionMessage('Missing route: "user"');
        $this->expectExceptionCode(1);

        /** @var RouteCollectionInterface|MockObject $routeCollection */
        $routeCollection = $this->getMockByCalls(RouteCollectionInterface::class, [
            Call::create('getRoutes')->with()->willReturn([]),
        ]);

        /** @var RouteParser|MockObject $routeParser */
        $routeParser = $this->getMockByCalls(RouteParser::class);

        $urlGenerator = new UrlGenerator($routeCollection, $routeParser);
        $urlGenerator->requestTarget('user', ['id' => 1]);
    }

    public function testRequestTargetWithMissingParameters(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->expectExceptionMessage('Missing parameters: "id"');
        $this->expectExceptionCode(2);

        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPath')->with()->willReturn('/user[/{id:\d+}]/{name}'),
        ]);

        /** @var RouteCollectionInterface|MockObject $routeCollection */
        $routeCollection = $this->getMockByCalls(RouteCollectionInterface::class, [
            Call::create('getRoutes')->with()->willReturn(['user' => $route]),
        ]);

        $parsedPath = [
            [
                '/user/',
                ['id', '\\d+'],
            ],
            [
                '/user/',
                ['id', '\\d+'],
                '/',
                ['name', '[^/]+'],
            ],
        ];

        /** @var RouteParser|MockObject $routeParser */
        $routeParser = $this->getMockByCalls(RouteParser::class, [
            Call::create('parse')->with('/user[/{id:\d+}]/{name}')->willReturn($parsedPath),
        ]);

        $urlGenerator = new UrlGenerator($routeCollection, $routeParser);
        $urlGenerator->requestTarget('user');
    }

    public function testRequestTargetSuccessful(): void
    {
        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPath')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPath')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPath')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPath')->with()->willReturn('/user[/{id:\d+}]/{name}'),
        ]);

        /** @var RouteCollectionInterface|MockObject $routeCollection */
        $routeCollection = $this->getMockByCalls(RouteCollectionInterface::class, [
            Call::create('getRoutes')->with()->willReturn(['user' => $route]),
        ]);

        $parsedPath = [
            [
                '/user/',
                ['id', '\\d+'],
            ],
            [
                '/user/',
                ['id', '\\d+'],
                '/',
                ['name', '[^/]+'],
            ],
        ];

        /** @var RouteParser|MockObject $routeParser */
        $routeParser = $this->getMockByCalls(RouteParser::class, [
            Call::create('parse')->with('/user[/{id:\d+}]/{name}')->willReturn($parsedPath),
            Call::create('parse')->with('/user[/{id:\d+}]/{name}')->willReturn($parsedPath),
            Call::create('parse')->with('/user[/{id:\d+}]/{name}')->willReturn($parsedPath),
            Call::create('parse')->with('/user[/{id:\d+}]/{name}')->willReturn($parsedPath),
        ]);

        $urlGenerator = new UrlGenerator($routeCollection, $routeParser);

        self::assertSame('/user/1', $urlGenerator->requestTarget('user', ['id' => 1]));
        self::assertSame('/user/1?key=value', $urlGenerator->requestTarget('user', ['id' => 1, 'key' => 'value']));
        self::assertSame('/user/1/sample', $urlGenerator->requestTarget('user', ['id' => 1, 'name' => 'sample']));
        self::assertSame(
            '/user/1/sample?key=value',
            $urlGenerator->requestTarget('user', ['id' => 1, 'name' => 'sample', 'key' => 'value'])
        );
    }
}
