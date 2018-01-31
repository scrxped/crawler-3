<?php

namespace Zstate\Crawler\Event;

use Symfony\Component\EventDispatcher\Event;

class BeforeEngineStarted extends Event
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}