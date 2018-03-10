<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;
use Zstate\Crawler\Client;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
    'start_uri' => ['https://httpbin.org/ip']
];

$client = new Client($config);

$client->addMiddleware(
    new class extends BaseMiddleware {
        public function processRequest(RequestInterface $request, array $options): RequestInterface
        {
            printf("Middleware 1 Request: %s \n", $request->getUri());
            return $request;
        }
        public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
        {
            printf("Middleware 1 Response: %s %s \n", $request->getUri(), $response->getStatusCode());
            return $response;
        }
    }
);

$client->addMiddleware(
    new class extends BaseMiddleware {
        public function processRequest(RequestInterface $request, array $options): RequestInterface
        {
            printf("Middleware 2 Request: %s \n", $request->getUri());
            return $request;
        }
        public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
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
Middleware 2 Request: https://httpbin.org/ip
Middleware 2 Response: https://httpbin.org/ip 200
Middleware 1 Response: https://httpbin.org/ip 200
*/
