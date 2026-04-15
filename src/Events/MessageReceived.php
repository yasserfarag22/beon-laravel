<?php

namespace Beon\Laravel\Events;

class MessageReceived
{
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
