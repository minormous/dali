<?php

declare(strict_types=1);

namespace Minormous\Dali\Exceptions;

use Exception;
use Throwable;

abstract class BaseException extends Exception
{
    final protected function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    protected function withPrevious(Throwable $previous): static
    {
        return new static($this->message, $this->code, $previous);
    }
}
