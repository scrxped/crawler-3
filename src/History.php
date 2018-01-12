<?php
namespace Zstate\Crawler;

use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;

interface History
{
    /**
     * @param RequestInterface $request
     * @return void
     */
    public function add(RequestInterface $request): void;

    /**
     * @param RequestInterface $request
     * @return boolean
     */
    public function contains(RequestInterface $request): bool;
}
