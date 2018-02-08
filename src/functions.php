<?php

namespace Zstate\Crawler;

use Psr\Http\Message\ResponseInterface;

function is_redirect(ResponseInterface $response): bool
{
    if (substr($response->getStatusCode(), 0, 1) != '3' || !$response->hasHeader('Location')) {
        return false;
    }

    return true;
}