<?php

declare(strict_types=1);

namespace Chubbyphp\Tests\Framework\Router\FastRoute;

use Chubbyphp\Framework\Router\FastRoute\UrlGenerator;
use Chubbyphp\Framework\Router\RouteCollectionInterface;
use Chubbyphp\Framework\Router\RouteInterface;
use Chubbyphp\Framework\Router\UrlGeneratorException;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use FastRoute\RouteParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Chubbyphp\Framework\Router\FastRoute\UrlGenerator
 */
final class UrlGeneratorTest extends TestCase
{
    use MockByCallsTrait;

    public function testGenerateUriSuccessful(): void
    {
        /** @var UriInterface|MockObject $uri */
        $uri = $this->getMockByCalls(UriInterface::class, [
            Call::create('getScheme')->with()->willReturn('https'),
            Call::create('getAuthority')->with()->willReturn('user:password@localhost'),
            Call::create('getScheme')->with()->willReturn('https'),
            Call::create('getAuthority')->with()->willReturn('user:password@localhost'),
            Call::create('getScheme')->with()->willReturn('https'),
            Call::create('getAuthority')->with()->willReturn('user:password@localhost'),
            Call::create('getScheme')->with()->willReturn('https'),
            Call::create('getAuthority')->with()->willReturn('user:password@localhost'),
        ]);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getUri')->with()->willReturn($uri),
            Call::create('getUri')->with()->willReturn($uri),
            Call::create('getUri')->with()->willReturn($uri),
            Call::create('getUri')->with()->willReturn($uri),
        ]);

        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
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

        self::assertSame(
            'https://user:password@localhost/user/1',
            $urlGenerator->generateUri($request, 'user', ['id' => 1])
        );
        self::assertSame(
            'https://user:password@localhost/user/1?key=value',
            $urlGenerator->generateUri($request, 'user', ['id' => 1, 'key' => 'value'])
        );
        self::assertSame(
            'https://user:password@localhost/user/1/sample',
            $urlGenerator->generateUri($request, 'user', ['id' => 1, 'name' => 'sample'])
        );
        self::assertSame(
            'https://user:password@localhost/user/1/sample?key=value',
            $urlGenerator->generateUri($request, 'user', ['id' => 1, 'name' => 'sample', 'key' => 'value'])
        );
    }

    public function testGeneratePathWithMissingRoute(): void
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
        $urlGenerator->generatePath('user', ['id' => 1]);
    }

    public function testGeneratePathWithMissingParameters(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->expectExceptionMessage('Missing parameters: "id"');
        $this->expectExceptionCode(2);

        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
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
        $urlGenerator->generatePath('user');
    }

    public function testGeneratePathWithInvalidParameters(): void
    {
        $this->expectException(UrlGeneratorException::class);
        $this->expectExceptionMessage(
            'Parameter "id" with value "c0b8bf5f-476b-4552-97aa-e37b8004a5c0" does not match "\d+"'
        );
        $this->expectExceptionCode(3);

        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
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
        $urlGenerator->generatePath('user', ['id' => 'c0b8bf5f-476b-4552-97aa-e37b8004a5c0']);
    }

    public function testGeneratePathSuccessful(): void
    {
        /** @var RouteInterface|MockObject $route */
        $route = $this->getMockByCalls(RouteInterface::class, [
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
            Call::create('getPattern')->with()->willReturn('/user[/{id:\d+}]/{name}'),
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

        self::assertSame('/user/1', $urlGenerator->generatePath('user', ['id' => 1]));
        self::assertSame('/user/1?key=value', $urlGenerator->generatePath('user', ['id' => 1, 'key' => 'value']));
        self::assertSame('/user/1/sample', $urlGenerator->generatePath('user', ['id' => 1, 'name' => 'sample']));
        self::assertSame(
            '/user/1/sample?key=value',
            $urlGenerator->generatePath('user', ['id' => 1, 'name' => 'sample', 'key' => 'value'])
        );
    }
}