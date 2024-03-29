<?php

namespace Zstate\Crawler\Tests;

use PHPUnit\Framework\TestCase;
use Zstate\Crawler\Client;
use Zstate\Crawler\Tests\Middleware\LogMiddleware;
use Zstate\Crawler\Tests\Middleware\HistoryMiddleware;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessFailure;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessRequest;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessResponse;

class ClientTest extends TestCase
{
    private $debug = false;

    public function testMultiDomainRequests()
    {
        $client = $this->getClient('http://site1.local/');

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);
        $client->run();


        $expected = [
            'GET http://site1.local/',
            'GET http://site1.local/customers.html',
            'GET http://site2.local',
            'GET http://site2.local/service.html',
            'GET http://site2.local/contacts.html',
        ];

        $this->assertEquals($expected, $history->getHistory());
    }

    public function testAllowUri()
    {
        $config = [
            'start_uri' => ['http://site1.local/testallowuri/'],
            'concurrency' => 1,
            'request_options' => [
                'debug' => $this->debug,
            ],
            'filter' => [
                'allow' => ['/testallowuri/page1','/testallowuri/page2']
            ]
        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);
        $client->run();


        $expected = [
            'GET http://site1.local/testallowuri/',
            'GET http://site1.local/testallowuri/page1.html',
            'GET http://site1.local/testallowuri/page2.html',
        ];

        $this->assertEquals($expected, $history->getHistory());
    }

    public function testDenyUri()
    {
        $config = [
            'start_uri' => ['http://site1.local/testallowuri/'],
            'concurrency' => 1,
            'request_options' => [
                'debug' => $this->debug,
            ],
            'filter' => [
                'deny' => ['/testallowuri/page1','/testallowuri/page2']
            ]
        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);
        $client->run();


        $expected = [
            'GET http://site1.local/testallowuri/',
            'GET http://site1.local/testallowuri/page3.html',
        ];

        $this->assertEquals($expected, $history->getHistory());
    }

    public function testDenyAllowUri()
    {
        $config = [
            'start_uri' => ['http://site1.local/testallowuri/'],
            'concurrency' => 1,
            'request_options' => [
                'debug' => $this->debug,
            ],
            'filter' => [
                'allow' => ['/testallowuri/page1','/testallowuri/page2'],
                'deny' => ['/testallowuri/page1']
            ]
        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);
        $client->run();


        $expected = [
            'GET http://site1.local/testallowuri/',
            'GET http://site1.local/testallowuri/page2.html',
        ];

        $this->assertEquals($expected, $history->getHistory());
    }

    public function testAnchorsLinks()
    {
        $config = [
            'start_uri' => ['http://site1.local/about/'],
            'concurrency' => 4,
            'request_options' => [
                'debug' => $this->debug,
            ],

        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/about/',

        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function testAllowDomains()
    {
        $config = [
            'start_uri' => ['http://site1.local/'],
            'request_options' => [
                'debug' => $this->debug,
            ],
            'filter' => [
                'allow_domains' => ['site1.local'],
            ],
            'concurrency' => 4
        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/',
            'GET http://site1.local/customers.html'
        ];
        $this->assertEquals($expected, $history->getHistory());
    }



    public function testLinkLoop()
    {
        $client = $this->getClient('http://site1.local/products/great-product.html');

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/products/great-product.html',
            'GET http://site1.local/products/awesome-product.html',
            'GET http://site1.local/products/super-product.html'
        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function testAsyncRequests()
    {
        $client = $this->getClient('http://site2.local/async/');

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site2.local/async/',
            'GET http://site2.local/async/delay3.php',
            'GET http://site2.local/async/delay2.php',
            'GET http://site2.local/async/delay1.php',
        ];
        $this->assertEquals($expected, $history->getHistory());
    }


    public function testAutoThrottle()
    {
        $config = [
            'start_uri' => ['http://site2.local/async/delay3.php', 'http://site2.local/async/delay2.php'],
            'concurrency' => 1,
            'autothrottle' => ['enabled' => true]
        ];

        $client = new Client($config);

        $startTime = microtime(true);
        $client->run();
        $endTime = microtime(true);

        $totalTimeInSeconds = ($endTime - $startTime);

        $this->assertGreaterThan(5, $totalTimeInSeconds);
    }

    public function testAutoThrottleIgnore404()
    {
        $config = [
            'start_uri' => ['http://site2.local/async/delay3.php', 'http://site1.local/404-error.php', 'http://site2.local/async/delay2.php'],
            'concurrency' => 1,
        ];

        $client = new Client($config);

        $startTime = microtime(true);
        $client->run();
        $endTime = microtime(true);

        $totalTimeInSeconds = ($endTime - $startTime);

        $this->assertGreaterThan(6.5, $totalTimeInSeconds);
    }

    public function testSamePageRequest()
    {
        $client = $this->getClient('http://site1.local/same-page-request.php');

        $history = new HistoryMiddleware;
        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/same-page-request.php',
            'GET http://site1.local/same-page-request.php?productId=1',
        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function test500ServerError()
    {
        $client = $this->getClient('http://site1.local/500-error.php');

        $log = new LogMiddleware;

        $client->addResponseMiddleware($log);

        $client->run();

        $expected = [
            'Process Response: http://site1.local/500-error.php status:500',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testCrawlerWontStopOnServerError()
    {
        $client = $this->getClient('http://site1.local/never-stop-crawling.html');

        $log = new LogMiddleware;

        $client->addResponseMiddleware($log);

        $client->run();

        $expected = [
            'Process Response: http://site1.local/never-stop-crawling.html status:200',
            'Process Response: http://site1.local/page-with-link-to-500-error.html status:200',
            'Process Response: http://site1.local/404-error.php status:404',
            'Process Response: http://site1.local/customers.html status:200',
            'Process Response: http://site1.local/500-error.php status:500',
        ];

        $this->assertEquals($expected, $log->getLog());
    }

    public function test404ServerError()
    {
        $client = $this->getClient('http://site1.local/404-error.php');

        $log = new LogMiddleware;

        $client->addResponseMiddleware($log);

        $client->run();

        $expected = [
            'Process Response: http://site1.local/404-error.php status:404',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    private function getClient($startUrl)
    {
        $config = [
            'start_uri' => [$startUrl],
            'concurrency' => 1,
            'request_options' => [
                'debug' => $this->debug,
            ]
        ];
        $client = new Client($config);

        return $client;
    }

    public function testStartUrlIsNotSet()
    {
        $this->expectException(\RuntimeException::class);

        $config = [
            'request_options' => [
                'debug' => $this->debug,
            ]
        ];
        $client = new Client($config);

        $client->run();
    }

    public function testRedirectsAndUriResolver()
    {
        $client = $this->getClient('http://site1.local/redirect/');

        $log = new LogMiddleware;

        $client->addResponseMiddleware($log);

        $client->run();

        $expected = [
            'Process Response: http://site1.local/redirect/ status:302',
            'Process Response: http://site1.local/redirect/index1.php status:302',
            'Process Response: http://site1.local/redirect/other.html status:200',
            'Process Response: http://site1.local/redirect/other.html?test=1 status:200',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testRequestDepth()
    {
        $config = [
            'start_uri' => ['http://site1.local/'],
            'concurrency' => 1,
            'depth' => 2
        ];
        $log = new LogMiddleware;

        $client = new Client($config);
        $client->addResponseMiddleware($log);

        $client->run();

        $expected = [
            'Process Response: http://site1.local/ status:200',
            'Process Response: http://site1.local/customers.html status:200',
            'Process Response: http://site2.local status:200',
        ];

        $this->assertEquals($expected, $log->getLog());

    }
}
