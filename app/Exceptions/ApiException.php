<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Named constructors read cleanly at call sites, e.g.
    // throw ApiException::notFound('Order not found');
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404);
    }

    public static function badRequest(string $message): self
    {
        return new self($message, 400);
    }

    public static function unauthorized(string $message = 'Not authorized'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }

    public static function paymentRequired(string $message): self
    {
        return new self($message, 402);
    }
}