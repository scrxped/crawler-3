<?php

namespace Zstate\Crawler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

function is_redirect(ResponseInterface $response): bool
{
    if (substr($response->getStatusCode(), 0, 1) != '3' || !$response->hasHeader('Location')) {
        return false;
    }

    return true;
}

function is_uri_matched_pattern(UriInterface $uri, string $pattern): bool
{
    $pattern = preg_quote($pattern, '/');

    $match = preg_match("/" . $pattern . "/i", (string) $uri);

    if(false === $match) {
        throw new \InvalidArgumentException('Invalid pattern: ' . $pattern);
    }

    return (bool) $match;
}