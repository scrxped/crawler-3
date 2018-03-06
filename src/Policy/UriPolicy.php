<?php

namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;

interface UriPolicy
{
    public function isUriAllowed(AbsoluteUri $uri): bool;
}