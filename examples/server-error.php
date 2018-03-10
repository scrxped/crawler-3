<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;
use Zstate\Crawler\Client;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
    'start_uri' => ['https://httpbin.org/status/500','https://httpbin.org/status/404'],
    'concurrency' => 1,
];

$client = new Client($config);

$client->withLog(
    new class extends BaseMiddleware {
        public function processFailure(RequestInterface $request, \Exception $reason): \Exception
        {
            printf("Process Failure: %s %s \n", $request->getUri(), $reason->getMessage());

            return $reason;
        }
    }
);

$client->run();

/*
Output:
Process Failure: https://httpbin.org/status/500 Server error: `GET https://httpbin.org/status/500` resulted in a `500 INTERNAL SERVER ERROR` response
Process Failure: https://httpbin.org/status/404 Client error: `GET https://httpbin.org/status/404` resulted in a `404 NOT FOUND` response
*/