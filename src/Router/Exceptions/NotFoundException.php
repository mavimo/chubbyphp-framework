<?php

declare(strict_types=1);

namespace Chubbyphp\Framework\Router\Exceptions;

use Chubbyphp\Framework\Router\RouterException;

final class NotFoundException extends RouterException implements RouterExceptionInterface
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

    public static function create(string $path): self
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
