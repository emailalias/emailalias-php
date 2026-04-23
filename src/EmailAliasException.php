<?php

declare(strict_types=1);

namespace EmailAlias;

use RuntimeException;

class EmailAliasException extends RuntimeException
{
    public int $status;

    public function __construct(string $message, int $status = 0)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

class AuthenticationException extends EmailAliasException {}
class NotFoundException extends EmailAliasException {}
class RateLimitException extends EmailAliasException {}
