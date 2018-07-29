<?php
declare(strict_types=1);


namespace Zstate\Crawler;

use Zstate\Crawler\Http\HttpClient;


/**
 * @package Zstate\Crawler
 */
class Session
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}
