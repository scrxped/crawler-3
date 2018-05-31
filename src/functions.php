<?php

namespace Zstate\Crawler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @param ResponseInterface $response
 * @return bool
 */
function is_redirect(ResponseInterface $response): bool
{
    if (substr($response->getStatusCode(), 0, 1) != '3' || !$response->hasHeader('Location')) {
        return false;
    }

    return true;
}

/**
 * @param UriInterface $uri
 * @param string $pattern
 * @return bool
 */
function is_uri_matched_pattern(UriInterface $uri, string $pattern): bool
{
    $pattern = preg_quote($pattern, '/');

    $match = preg_match("/" . $pattern . "/i", (string) $uri);

    if(false === $match) {
        throw new \InvalidArgumentException('Invalid pattern: ' . $pattern);
    }

    return (bool) $match;
}