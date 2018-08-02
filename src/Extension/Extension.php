<?php
declare(strict_types=1);


namespace Zstate\Crawler\Extension;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Session;
use Zstate\Crawler\Storage\QueueInterface;

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
     * @var QueueInterface
     */
    private $queue;

    /**
     * @param Config $config
     * @param Session $session
     * @param QueueInterface $queue
     */
    public function initialize(Config $config, Session $session, QueueInterface $queue)
    {
        $this->config = $config;
        $this->session = $session;
        $this->queue = $queue;
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

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }
}
