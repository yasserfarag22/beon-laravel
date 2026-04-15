<?php

namespace Beon\Laravel\Events;

class MessageStatusUpdated
{
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
