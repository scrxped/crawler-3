<?php
declare(strict_types=1);


namespace Zstate\Crawler\Extension;


use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Session;

/**
 * @package Zstate\Crawler\Extension
 */
abstract class Extension implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Config $config
     * @param Session $session
     */
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

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }
}