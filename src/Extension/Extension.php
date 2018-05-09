<?php
declare(strict_types=1);


namespace Zstate\Crawler\Extension;


use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Session;

abstract class Extension implements EventSubscriberInterface
{
    private $config;

    private $session;

    public function initialize(Config $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getSession(): Session
    {
        return $this->session;
    }
}