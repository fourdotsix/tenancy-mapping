<?php

namespace Fourdotsix\TenancyMapping\Exceptions;

use Exception;

class MappingFileNotFound extends Exception
{
    public function __construct(?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        $message ??= 'Mapping File not found!';
        parent::__construct($message, $code, $previous);
    }
}
