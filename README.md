[![Build Status](https://travis-ci.org/zstate/crawler.svg?branch=master)](https://travis-ci.org/zstate/crawler)
[![Coverage Status](https://coveralls.io/repos/github/zstate/crawler/badge.svg)](https://coveralls.io/github/zstate/crawler)

# Overview

Crawler is a fast asynchronous internet bot aiming to provide open source web search and testing solution.
It can be used for a wide range of purposes, from extracting and indexing structured data to monitoring and automated testing.

## Key Features

- Asynchronous crawling with customizable concurrency.
- Automatically throttling crawling speed based on the load of the website you are crawling
- If configured, automatically filters out requests forbidden by the `robots.txt` exclusion standard.
- Straightforward middleware system allows you to append headers, extract data, filter or plug any custom functionality to process the request and response.
- Rich filtering capabilities.
- Easy to extend the core by hooking into the crawling process using events.


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
To create middleware simply implement `Zstate\Crawler\Middleware\RequestMiddleware` or `Zstate\Crawler\Middleware\ResponseMiddleware` and
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

To skip the request and go to the next from your middleware you can `throw new \Zstate\Crawler\Exception\InvalidRequestException`. 
The scheduler will catch the exception, notify all subscribers, and ignore the request.  

## Processing server errors

You can use middlewares to handle 4xx or 5xx responses.

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

## Filtering

Use regular expression to allow or deny specific links. You can also pass array of allowed or denied domains as well. 
Use `robotstxt_obey` option to enable filtering. out requests forbidden by the `robots.txt` exclusion standard

```php

...
$config = [
    'start_uri' => ['http://site.local/'],
    'concurrency' => 1,
    'filter' => [
        'robotstxt_obey' => true,
        'allow' => ['/page\d+','/otherpage'],
        'deny' => ['/logout']
        'allow_domains' => ['site.local'],
        'deny_domains' => ['othersite.local'],
    ]
];
$client = new Client($config);

```

## Autothrottle

Autothrottle is enabled by default (use `autothrottle.enabled => false` to disable). It automatically adjusts scheduler to the optimum crawling speed trying to be nicer to the sites.


**Throttling algorithm**

AutoThrottle algorithm adjusts download delays based on the following rules:

1. When a response is received, the target download delay is calculated as `latency / N` where latency is a latency of the response, and `N` is concurrency.
3. Delay for next requests is set to the average of previous delay and the current delay;
4. Latencies of non-200 responses are not allowed to decrease the delay;
5. Delay can’t become less than `min_delay` or greater than `max_delay`


```php

...
$config = [
    'start_uri' => ['http://site.local/'],
    'concurrency' => 3,
    'auto_throttle' => [
        'enabled' => true,
        'min_delay' => 0, // Sets minimum delay between the requests (default 0).
        'max_delay' => 60, // Sets maximun delay between the requests (default 60).
    ]
];

$client = new Client($config);
...

```

## Extension

Basically speaking, extensions are nothing more than event listeners based on the Symfony Event Dispatcher component.
To create extension simply extend `Zstate\Crawler\Extension\Extension` and add it to a client. All extensions have access to a 
`Zstate\Crawler\Config\Config` and `Zstate\Crawler\Session` object, which holds `GuzzleHttp\Client`. This might be helpful if you want to 
make some additional requests or reuse cookie headers for authentication.

```php
...

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Client;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Extension\Extension;
use Zstate\Crawler\Middleware\ResponseMiddleware;

$config = [
    'start_uri' => ['http://site.local/admin/']
];

$client = new Client($config);

$loginUri = 'http://site.local/admin/';
$username = 'test';
$password = 'password';

$client->addExtension(new class($loginUri, $username, $password) extends Extension {
    private $loginUri;
    private $username;
    private $password;

    public function __construct(string $loginUri, string $username, string $password)
    {
        $this->loginUri = $loginUri;
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate(BeforeEngineStarted $event): void
    {
        $this->login($this->loginUri, $this->username, $this->password);
    }

    private function login(string $loginUri, string $username, string $password)
    {
        $formParams = ['username' => $username, 'password' => $password];
        $body = http_build_query($formParams, '', '&');
        $request = new Request('POST', $loginUri, ['content-type' => 'application/x-www-form-urlencoded'], $body);
        $this->getSession()->getHttpClient()->send($request);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEngineStarted::class => 'authenticate'
        ];
    }
});

$client->run();

```

**List of supported events `Zstate\Crawler\Event`:**

| Event                     | When?                                         |
| ------------------------- | --------------------------------------------- |
| BeforeEngineStarted       | Right before the engine starts crawling       |
| BeforeRequestSent         | Before the request is scheduled to be sent    |
| AfterRequestSent          | After the request is scheduled                |
| TransferStatisticReceived | When a handler has finished sending a request. Allows you to get access to transfer statistics of a request and access the lower level transfer details. |
| ResponseHeadersReceived   | When the HTTP headers of the response have been received but the body has not yet begun to download. Useful if you want to reject responses that are greater than certain size for example. |
| RequestFailed             | When the request is failed or the exception `InvalidRequestException` has been  thrown in the middleware. |
| ResponseReceived          | When the response is received                 |
| AfterEngineStopped        | After engine stopped crawling                 |


## Command Line Tool

You can use simple command line tool to crawl your site quickly.
First create configuration file:

```bash
./crawler init 

```

Then configure `crawler.yml` and run the crawler with a command:

```bash
./crawler start --config=./crawler.yml 

```
To get more details about request and response use `-vvv` option:

```bash
./crawler start --config=./crawler.yml -vvv 

```

## Thanks for Inspiration

https://scrapy.org/

http://docs.guzzlephp.org/




