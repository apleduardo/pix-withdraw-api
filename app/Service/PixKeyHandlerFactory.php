<?php
namespace App\Service;

class PixKeyHandlerFactory
{
    protected array $handlers = [
        'email' => PixEmailHandler::class,
    ];

    public function getHandler(string $type): ?PixKeyHandlerInterface
    {
        return isset($this->handlers[$type]) ? new $this->handlers[$type]() : null;
    }
}
