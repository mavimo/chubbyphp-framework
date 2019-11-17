<?php

declare(strict_types=1);

namespace Chubbyphp\Framework\Router;

use Chubbyphp\Framework\Middleware\RouterMiddleware;

final class RouterException extends \RuntimeException
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $title;

    private function __construct(string $message, int $code, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function createForNotFound(string $path): self
    {
        $self = new self(sprintf(
            'The page "%s" you are looking for could not be found.'
                .' Check the address bar to ensure your URL is spelled correctly.',
            $path
        ), 404);
        $self->type = 'https://tools.ietf.org/html/rfc7231#section-6.5.4';
        $self->title = 'Page not found';

        return $self;
    }

    /**
     * @param array<string> $methods
     */
    public static function createForMethodNotAllowed(string $method, array $methods, string $path): self
    {
        $self = new self(sprintf(
            'Method "%s" at path "%s" is not allowed. Must be one of: "%s"',
            $method,
            $path,
            implode('", "', $methods)
        ), 405);
        $self->type = 'https://tools.ietf.org/html/rfc7231#section-6.5.5';
        $self->title = 'Method not allowed';

        return $self;
    }

    public static function createForMissingRoute(string $name): self
    {
        return new self(sprintf('Missing route: "%s"', $name), 1);
    }

    /**
     * @param mixed $route
     */
    public static function createForMissingRouteAttribute($route): self
    {
        return new self(sprintf(
            'Request attribute "route" missing or wrong type "%s", please add the "%s" middleware',
            is_object($route) ? get_class($route) : gettype($route),
            RouterMiddleware::class
        ), 2);
    }

    public static function createForPathGenerationMissingAttribute(string $name, string $attribute): self
    {
        return new self(sprintf('Missing attribute "%s" while path generation for route: "%s"', $attribute, $name), 3);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
