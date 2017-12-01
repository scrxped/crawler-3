<?php

namespace Zstate\Crawler\Tests\Middleware;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zstate\Crawler\Client;
use Zstate\Crawler\Handler\MockHandler;
use Zstate\Crawler\Tests\Middleware\LogMiddleware;
use Zstate\Crawler\Repository\InMemoryHistory;
use Zstate\Crawler\Service\LinkExtractor;

class MiddlewareWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function testMiddleware()
    {
        $handler = new MockHandler([
            new Response(200, [], '<a href="/test.html">test</a>'),
            new Response(200, [], '<a href="/test1.html">test1</a>'),
            new Response(200, [], '<a href="/test2.html">test2</a>'),
            new Response(200, [], '<a href="/test3.html">test3</a><a href="/test1.html">test1</a><a href="/test.html">test</a><a href="/test2.html">test2</a>'),
        ]);

        $crawler = $this->getClient($handler);

        $history = new HistoryMiddleware;
        $crawler->addMiddleware($history);

        $crawler->run();

        $this->assertEquals([
            'GET http://site1.local/',
            'GET http://site1.local/test.html',
            'GET http://site1.local/test1.html',
            'GET http://site1.local/test2.html',
            'GET http://site1.local/test3.html',
        ], $history->getHistory());
    }

    public function test500Error()
    {
        $handler = new MockHandler([
            new Response(500, ['X-Foo' => 'Bar'], '500 Error'),
        ]);

        $crawler = $this->getClient($handler);

        $history = new HistoryMiddleware;
        $crawler->addMiddleware($history);

        $crawler->run();

        $this->assertEquals([
            'GET http://site1.local/',
        ], $history->getHistory());
    }

    public function test404Error()
    {
        $handler = new MockHandler([
            new Response(404, [], '404 Error'),
        ]);

        $crawler = $this->getClient($handler);
        $history = new HistoryMiddleware;
        $crawler->addMiddleware($history);
        $crawler->run();

        $this->assertEquals([
            'GET http://site1.local/',
        ], $history->getHistory());
    }

    public function testExceptionInProcessRequest()
    {
        $handler = new MockHandler([
            new Response(200, [], '<a href="/test.html">test</a>'),
            new Response(200, [], '<a href="/test1.html">test1</a>'),
        ]);

        $crawler = $this->getClient($handler);
        $history = new HistoryMiddleware;
        $crawler->addMiddleware($history);

        $crawler->addMiddleware(new MiddlewareWithExceptionInProcessRequest);

        $crawler->run();

        $this->assertEquals([
            'GET http://site1.local/',
        ], $history->getHistory());
    }

    public function testExceptionInProcessResponse()
    {
        $handler = new MockHandler([
            new Response(200, [], '<a href="/test.html">test</a>'),
            new Response(200, [], '<a href="/test1.html">test1</a>'),
        ]);

        $crawler = $this->getClient($handler);
        $history = new HistoryMiddleware;
        $crawler->addMiddleware($history);

        $crawler->addMiddleware(new MiddlewareWithExceptionInProcessResponse);

        $crawler->run();

        $this->assertEquals([
            'GET http://site1.local/',
        ], $history->getHistory());
    }

    /**
     * @param $handler
     * @return Client
     */
    private function getClient($handler)
    {
        $linkExtractor = new LinkExtractor;

        $crawler = new Client(
            $handler,
            new InMemoryHistory,
            $linkExtractor,
            ['start_url' => 'http://site1.local/']
        );

        return $crawler;
    }
}
