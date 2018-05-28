[![Build Status](https://travis-ci.org/zstate/crawler.svg?branch=master)](https://travis-ci.org/zstate/crawler)
[![Coverage Status](https://coveralls.io/repos/github/zstate/crawler/badge.svg)](https://coveralls.io/github/zstate/crawler)

# Overview

Crawler is a fast asynchronous internet bot aiming to provide open source web search and testing solution for local websites.
It can be used for a wide range of purposes, from extracting and indexing structured data to monitoring and automated testing.


## Quick Start
```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;
use Zstate\Crawler\Client;
use Zstate\Crawler\Middleware\ResponseMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
    'start_uri' => ['https://httpbin.org/'],
    'concurrency' => 3,
    'filter' => [
        //A list of string containing domains which will be considered for extracting the links.
        'allow_domains' => ['httpbin.org'],
        //A list of regular expressions that the urls must match in order to be extracted.
        'allow' => ['/get','/ip','/anything']
    ]
];

$client = new Client($config);

$client->addResponseMiddleware(
    new class implements ResponseMiddleware {
        public function processResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface
        {
            printf("Process Response: %s %s \n", $request->getUri(), $response->getStatusCode());

            return $response;
        }
    }
);

$client->run();
```

## Middlewares

Middleware can be written to perform a variety of tasks including authentication, filtering, headers, logging, etc.
To create middleware simply implement `Zstate\Crawler\Middleware\Middleware` or extend `Zstate\Crawler\Middleware\BaseMiddleware` and
then add it to a client:


```php
...

$config = [
    'start_uri' => ['https://httpbin.org/ip']
];

$client = new Client($config);

$client->addRequestMiddleware(
    new class implements RequestMiddleware {
        public function processRequest(RequestInterface $request): RequestInterface
        {
            printf("Middleware 1 Request: %s \n", $request->getUri());
            return $request;
        }
    }
);

$client->addResponseMiddleware(
    new class implements ResponseMiddleware {
        public function processResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface
        {
            printf("Middleware 2 Response: %s %s \n", $request->getUri(), $response->getStatusCode());
            return $response;
        }
    }
);

$client->run();

/*
Output:
Middleware 1 Request: https://httpbin.org/ip
Middleware 2 Response: https://httpbin.org/ip 200
*/

```

## Processing server errors

To handle 4xx or 5xx responses create middleware and implement desired behavior in `processFailure` method.

```php
...
$config = [
    'start_uri' => ['https://httpbin.org/status/500','https://httpbin.org/status/404'],
    'concurrency' => 1,
];

$client = new Client($config);

$client->addResponseMiddleware(
    new class implements ResponseMiddleware {
        public function processResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface
        {
            printf("Process Failure: %s %s \n", $request->getUri(), $response->getStatusCode());

            return $response;
        }
    }
);

$client->run();
```





