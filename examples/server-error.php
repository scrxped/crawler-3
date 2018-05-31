<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;
use Zstate\Crawler\Client;
use Zstate\Crawler\Middleware\ResponseMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

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
