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
        $client->addMiddleware($history);
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

    public function testAnchorsLinks()
    {
        $config = [
            'start_uri' => 'http://site1.local/about/',
            'concurrency' => 4,
            'request_options' => [
                'debug' => $this->debug,
            ],

        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;
        $client->addMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/about/',

        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function testAllowDomains()
    {
        $config = [
            'start_uri' => 'http://site1.local/',
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
        $client->addMiddleware($history);

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
        $client->addMiddleware($history);
        $client->withLog(new LogMiddleware);

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
        $client->addMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site2.local/async/',
            'GET http://site2.local/async/delay3.php',
            'GET http://site2.local/async/delay2.php',
            'GET http://site2.local/async/delay1.php',
        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function testSamePageRequest()
    {
        $client = $this->getClient('http://site1.local/same-page-request.php');

        $history = new HistoryMiddleware;
        $client->addMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/same-page-request.php',
            'GET http://site1.local/same-page-request.php?productId=1',
        ];
        $this->assertEquals($expected, $history->getHistory());
    }

    public function testSite2AuthMiddleware()
    {
        $config = [
            'start_uri' => 'http://site2.local/admin/',
            'login' => [
                'login_uri' => 'http://site2.local/admin/login.php',
                'form_params' => ['username' => 'test', 'password' => 'password']
            ],
            'request_options' => [
                'debug' => $this->debug,
            ]
        ];
        $client = new Client($config);

        $log = new LogMiddleware;

        $client->withLog($log);

        $client->run();

        // Getting more results due to redirects
        $expected = [
            0 => 'Process Request: POST http://site2.local/admin/login.php username=test&password=password',
            1 => 'Process Response: http://site2.local/admin/login.php status:302',
            2 => 'Process Request: GET http://site2.local/admin/',
            3 => 'Process Response: http://site2.local/admin/ status:200',
            4 => 'Process Request: GET http://site2.local/admin/restricted.php',
            5 => 'Process Response: http://site2.local/admin/restricted.php status:200',
            6 => 'Process Request: GET http://site2.local/admin/logout.php',
            7 => 'Process Response: http://site2.local/admin/logout.php status:302',
        ];


        $this->assertEquals($expected, $log->getLog());
    }

    public function test500ServerError()
    {
        $client = $this->getClient('http://site1.local/500-error.php');

        $log = new LogMiddleware;

        $client->addMiddleware(new HistoryMiddleware);
        $client->withLog($log);

        $client->run();

        $expected = [
            'Process Request: GET http://site1.local/500-error.php',
            'Process Failure: Server error: `GET http://site1.local/500-error.php` resulted in a `500 Internal Server Error` response',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testCrawlerWontStopOnServerError()
    {
        $client = $this->getClient('http://site1.local/never-stop-crawling.html');

        $log = new LogMiddleware;

        $client->withLog($log);

        $client->run();

        $expected = [
            0 => 'Process Request: GET http://site1.local/never-stop-crawling.html',
            1 => 'Process Response: http://site1.local/never-stop-crawling.html status:200',
            2 => 'Process Request: GET http://site1.local/page-with-link-to-500-error.html',
            3 => 'Process Response: http://site1.local/page-with-link-to-500-error.html status:200',
            4 => 'Process Request: GET http://site1.local/404-error.php',
            5 => 'Process Failure: Client error: `GET http://site1.local/404-error.php` resulted in a `404 Not Found` response',
            6 => 'Process Request: GET http://site1.local/customers.html',
            7 => 'Process Response: http://site1.local/customers.html status:200',
            8 => 'Process Request: GET http://site1.local/500-error.php',
            9 => 'Process Failure: Server error: `GET http://site1.local/500-error.php` resulted in a `500 Internal Server Error` response',
        ];

        $this->assertEquals($expected, $log->getLog());
    }

    public function test404ServerError()
    {
        $client = $this->getClient('http://site1.local/404-error.php');

        $log = new LogMiddleware;

        $client->withLog($log);

        $client->run();

        $expected = [
            'Process Request: GET http://site1.local/404-error.php',
            'Process Failure: Client error: `GET http://site1.local/404-error.php` resulted in a `404 Not Found` response',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testProcessRequestFailure()
    {
        $client = $this->getClient('http://site2.local/service.html');

        $log = new LogMiddleware;

        $client->withLog($log);
        $client->addMiddleware(new HistoryMiddleware);

        $client->addMiddleware(new MiddlewareWithExceptionInProcessRequest);


        $client->run();

        $expected = [
            'Process Request: GET http://site2.local/service.html',
            'Process Failure: Exception in MiddlewareWithExceptionInProcessRequest::processRequest',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testProcessResponseFailure()
    {
        $client = $this->getClient('http://site1.local/customers.html');

        $log = new LogMiddleware;

        $client->withLog($log);
        $client->addMiddleware(new HistoryMiddleware);

        $client->addMiddleware(new MiddlewareWithExceptionInProcessResponse);


        $client->run();

        $expected = [
            'Process Request: GET http://site1.local/customers.html',
            'Process Failure: Exception in MiddlewareWithExceptionInProcessResponse::processResponse',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    public function testProcessFailureException()
    {
        $client = $this->getClient('http://site2.local/service.html');

        $log = new LogMiddleware;

        $client->withLog($log);
        $client->addMiddleware(new HistoryMiddleware);

        $client->addMiddleware(new MiddlewareWithExceptionInProcessFailure);
        $client->addMiddleware(new MiddlewareWithExceptionInProcessResponse);


        $client->run();

        $expected = [
            'Process Request: GET http://site2.local/service.html',
            'Process Failure: Exception in MiddlewareWithExceptionInProcessFailure::processFailure',
        ];
        $this->assertEquals($expected, $log->getLog());
    }

    private function getClient($startUrl)
    {
        $config = [
            'start_uri' => $startUrl,
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

        $client->withLog($log);

        $client->run();

        $expected = [
            0 => 'Process Request: GET http://site1.local/redirect/',
            1 => 'Process Response: http://site1.local/redirect/ status:302',
            2 => 'Process Request: GET http://site1.local/redirect/index1.php',
            3 => 'Process Response: http://site1.local/redirect/index1.php status:302',
            4 => 'Process Request: GET http://site1.local/redirect/other.html',
            5 => 'Process Response: http://site1.local/redirect/other.html status:200',
            6 => 'Process Request: GET http://site1.local/redirect/other.html?test=1',
            7 => 'Process Response: http://site1.local/redirect/other.html?test=1 status:200',
        ];
        $this->assertEquals($expected, $log->getLog());
    }
}
