<?php
namespace Zstate\Crawler\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Interface HttpClient
 * @package Zstate\Crawler\Http
 */
interface HttpClient
{
    /**
     * @param RequestInterface $request
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request): PromiseInterface;
}