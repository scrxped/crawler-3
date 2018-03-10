[![Build Status](https://travis-ci.org/zstate/crawler.svg?branch=master)](https://travis-ci.org/zstate/crawler)
[![Coverage Status](https://coveralls.io/repos/github/zstate/crawler/badge.svg)](https://coveralls.io/github/zstate/crawler)

# Overview

Crawler is a fast asynchronous internet bot aiming to provide open source web search and testing solution for local websites.
It can be used for a wide range of purposes, from extracting and indexing structured data to monitoring and automated testing.


## Quick Start

    <?php
    
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;
    use Zstate\Crawler\Middleware\BaseMiddleware;
    use Zstate\Crawler\Client;
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $config = [
        'start_uri' => 'https://httpbin.org/',
        'concurrency' => 3,
        'filter' => [
            //A list of string containing domains which will be considered for extracting the links.
            'allow_domains' => ['httpbin.org'],
            //A list of regular expressions that the urls must match in order to be extracted.
            'allow' => ['/get','/ip','/anything']
        ]
    ];
    
    $client = new Client($config);
    
    $client->withLog(
        new class extends BaseMiddleware {
            public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
            {
                printf("Process Response: %s %s \n", $request->getUri(), $response->getStatusCode());
    
                return $response;
            }
        }
    );
    
    $client->run();
    
    /* 
    Output:
    Process Response: https://httpbin.org/ 200 
    Process Response: https://httpbin.org/ip 200 
    Process Response: https://httpbin.org/get 200 
    Process Response: https://httpbin.org/anything 200
    */
    
## Middlewares





