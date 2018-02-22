<?php

namespace Zstate\Crawler\Event;

use Symfony\Component\EventDispatcher\Event;
use Zstate\Crawler\Config\Config;

class BeforeEngineStarted extends Event
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}