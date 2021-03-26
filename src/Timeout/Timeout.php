<?php declare(strict_types=1);

namespace Bref\Timeout;

/**
 * Helper class to trigger an exception just before the Lambda times out. This
 * will give the application a chance to shut down.
 */
final class Timeout
{
    /** @var bool */
    private static $initialized = false;

    /**
     * Automatically setup a timeout (based on the AWS Lambda timeout).
     *
     * This method can only be called when running in PHP-FPM, i.e. when using a `php-XX-fpm` layer.
     */
    public static function enableInFpm(): void
    {
        if (isset($_SERVER['LAMBDA_INVOCATION_CONTEXT'])) {
            $context = json_decode($_SERVER['LAMBDA_INVOCATION_CONTEXT'], true, 512, JSON_THROW_ON_ERROR);
            $deadlineMs = $context['deadlineMs'];
            $remainingTimeInMillis = $deadlineMs - intval(microtime(true) * 1000);

            self::enable($remainingTimeInMillis);

            return;
        }

        throw new \LogicException('Could not find value for bref timeout. Are we running on Lambda?');
    }

    /**
     * @internal
     */
    public static function enable(int $remainingTimeInMillis): void
    {
        // The script will timeout 1 second before the remaining time
        // to allow some time for Bref/our app to recover and cleanup
        $margin = 1;

        $remainingTimeInSeconds = (int) floor($remainingTimeInMillis / 1000);
        self::timeoutAfter(max(1, $remainingTimeInSeconds - $margin));
    }

    /**
     * Setup custom handler for SIGTERM.
     */
    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        if (! function_exists('pcntl_async_signals')) {
            trigger_error('Could not enable timeout exceptions because pcntl extension is not enabled.');
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, function (): void {
            throw new LambdaTimeout('Maximum AWS Lambda execution time reached');
        });

        self::$initialized = true;
    }

    /**
     * Set a timer to throw an exception.
     */
    private static function timeoutAfter(int $seconds): void
    {
        self::init();
        pcntl_alarm($seconds);
    }

    /**
     * Reset timeout.
     *
     * @internal
     */
    public static function reset(): void
    {
        if (self::$initialized) {
            pcntl_alarm(0);
        }
    }
}
