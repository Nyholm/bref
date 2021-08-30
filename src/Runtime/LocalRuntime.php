<?php

declare(strict_types=1);


namespace Bref\Runtime;

use Bref\Context\Context;
use Bref\Event\Handler;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Local runtime for development.
 *
 * @internal
 */
final class LocalRuntime implements Runtime
{
    /** @var Invoker */
    private $invoker;
    
    public function __construct()
    {
        $this->invoker = new Invoker;
    }


    /**
     * @param Handler|RequestHandlerInterface|callable $handler If it is a callable, it takes two parameters, an $event parameter (mixed) and a $context parameter (Context) and must return anything serializable to JSON.
     */
    public function processNextEvent($handler): void
    {
        // TODO add support for populate $event from env variables
        $event = [];
        
        try {
            $result = $this->invoker->invoke($handler, $event, new Context('local', 10000, 'arn::local', 'trace-id'));

            $this->sendResponse($context->getAwsRequestId(), $result);
        } catch (\Throwable $e) {
            $this->failInitialization('Invocation failed', $e);
        }
        
        exit(0);
    }
    
    public function failInitialization(string $message, ?\Throwable $error = null): void
    {
        echo "$message\n";
        if ($error) {
            if ($error instanceof Exception) {
                $errorMessage = get_class($error) . ': ' . $error->getMessage();
            } else {
                $errorMessage = $error->getMessage();
            }
            printf(
                "Fatal error: %s in %s:%d\nStack trace:\n%s",
                $errorMessage,
                $error->getFile(),
                $error->getLine(),
                $error->getTraceAsString()
            );
        }

        exit(1);
    }
}