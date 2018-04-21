<?php
declare(strict_types=1);


namespace Zstate\Crawler\Extension;


use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Config\Config;

abstract class Extension implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    private $httpClient;

    public function initialize(Config $config, ClientInterface $client)
    {
        $this->config = $config;
        $this->httpClient = $client;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }
}