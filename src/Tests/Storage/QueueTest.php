<?php

namespace Zstate\Crawler\Tests\Storage;

use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;
use Zstate\Crawler\Storage\Adapter\SqliteDsn;
use Zstate\Crawler\Storage\Queue;

class QueueTest extends PHPUnit_Framework_TestCase
{
    private $adapter;

    public function setUp()
    {
        parent::setUp();

        $this->adapter = new SqliteAdapter(new SqliteDsn('sqlite::memory:'));
    }

    public function tearDown()
    {
        $this->adapter = null;

        parent::tearDown();
    }

    public function testEnqueueAndDequeue()
    {
        $queue = new Queue($this->adapter);

        $method = 'POST';
        $uri = '/test.html';
        $headers = ['content-type' => ['application/x-www-form-urlencoded']];
        $body = 'username=test&password=test';

        $request = new Request(
            $method,
            $uri,
            $headers,
            $body
        );

        $queue->enqueue($request);

        $requestFromQueue = $queue->dequeue();

        $this->assertEquals($method, $requestFromQueue->getMethod());
        $this->assertEquals($uri, (string)$requestFromQueue->getUri());
        $this->assertEquals($headers, $requestFromQueue->getHeaders());
        $this->assertEquals($body, (string) $requestFromQueue->getBody());
    }

    public function testIsEmptyQueue()
    {
        $queue = new Queue($this->adapter);

        $this->assertTrue($queue->isEmpty());

        $queue->enqueue(new Request('GET','/test.html'));
        $queue->enqueue(new Request('GET','/test.html'));

        $this->assertFalse($queue->isEmpty());

        $queue->dequeue();

        $this->assertTrue($queue->isEmpty());
    }



}