<?php

declare(strict_types=1);


namespace Bref\Runtime;


use Bref\Event\Handler;
use Psr\Http\Server\RequestHandlerInterface;

interface Runtime
{
    /**
     * @param Handler|RequestHandlerInterface|callable $handler If it is a callable, it takes two parameters, an $event parameter (mixed) and a $context parameter (Context) and must return anything serializable to JSON.
     */
    public function processNextEvent($handler): void;
    public function failInitialization(string $message, ?\Throwable $error = null): void;
}