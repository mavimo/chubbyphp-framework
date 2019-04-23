<?php

declare(strict_types=1);

namespace Chubbyphp\Framework\Router;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface RouteInterface
{
    const DELETE = 'DELETE';
    const GET = 'GET';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const PATCH = 'PATCH';
    const POST = 'POST';
    const PUT = 'PUT';

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler(): RequestHandlerInterface;

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array;

    /**
     * @param array $attributes
     *
     * @return RouteInterface
     */
    public function withAttributes(array $attributes): RouteInterface;

    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @return string
     */
    public function __toString(): string;
}
