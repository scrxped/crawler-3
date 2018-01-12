<?php

namespace Zstate\Crawler\Tests;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zstate\Crawler\Client;
use Zstate\Crawler\Tests\Middleware\LogMiddleware;
use Zstate\Crawler\Tests\Middleware\HistoryMiddleware;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessFailure;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessRequest;
use Zstate\Crawler\Tests\Middleware\MiddlewareWithExceptionInProcessResponse;

class ClientTest extends \PHPUnit_Framework_TestCase
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

    public function testHashLinks()
    {
        $config = [
            'start_url' => 'http://site1.local/about/',
            'debug' => $this->debug,
            'concurrency' => 1
        ];
        $client = Client::create($config);

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
            'start_url' => 'http://site1.local/',
            'debug' => $this->debug,
            'allow_domains' => ['site1.local'],
            'concurrency' => 7
        ];
        $client = Client::create($config);

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

    public function testSite2AuthMiddleware()
    {
        $config = [
            'start_url' => 'http://site2.local/admin/',
            'debug' => $this->debug,
        ];
        $client = Client::create($config);

        $history = new HistoryMiddleware;

        $client->withAuth([
            'loginUri' => 'http://site2.local/admin/login.php',
            'form_params' => ['username' => 'test', 'password' => 'password']
        ]);
        $client->addMiddleware($history);

        $client->withLog(new LogMiddleware);

        $client->run();

        // Getting more results due to redirects
        $expected = [
            0 => 'POST http://site2.local/admin/login.php username=test&password=password',
            1 => 'GET http://site2.local/admin/',
            2 => 'GET http://site2.local/admin/login.php',
            3 => 'GET http://site2.local/admin/',
            4 => 'GET http://site2.local/admin/restricted.php',
            5 => 'GET http://site2.local/admin/logout.php',
            6 => 'GET http://site2.local/admin/',
            7 => 'GET http://site2.local/admin/login.php',
            8 => 'GET http://site2.local/admin/',
            9 => 'GET http://site2.local/admin/login.php',
        ];


        $this->assertEquals($expected, $history->getHistory());
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
            'start_url' => $startUrl,
            'debug' => $this->debug,
            'concurrency' => 7
        ];
        $client = Client::create($config);

        return $client;
    }

    public function testStartUrlIsNotSet()
    {
        $this->expectException(\RuntimeException::class);

        $config = [
            'debug' => $this->debug
        ];
        $client = Client::create($config);

        $client->run();
    }
}
