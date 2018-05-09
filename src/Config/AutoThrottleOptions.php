<?php
declare(strict_types=1);


namespace Zstate\Crawler\Config;


/**
 * Class AutoThrottleOptions
 * @package Zstate\Crawler\Config
 * @internal
 */
class AutoThrottleOptions
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->options['enabled'];
    }

    public function getMinDelay(): int
    {
        return $this->options['min_delay'];
    }

    public function getMaxDelay(): int
    {
        return $this->options['max_delay'];
    }
}