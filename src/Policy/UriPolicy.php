<?php

namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;

/**
 * @package Zstate\Crawler\Policy
 */
interface UriPolicy
{
    /**
     * @param AbsoluteUri $uri
     * @return bool
     */
    public function isUriAllowed(AbsoluteUri $uri): bool;
}