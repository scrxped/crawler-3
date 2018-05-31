<?php
declare(strict_types=1);


namespace Zstate\Crawler;

use GuzzleHttp\ClientInterface;

/**
 * @package Zstate\Crawler
 */
class Session
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }
}
