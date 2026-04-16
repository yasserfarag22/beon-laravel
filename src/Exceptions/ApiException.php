<?php

namespace Beon\Laravel\Exceptions;

class ApiException extends BeonException
{
    protected array $response;

    public function __construct(string $message, int $code = 0, array $response = [], \Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
