<?php

namespace Zstate\Crawler\Tests\Storage;

use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase;
use Zstate\Crawler\Service\StorageService;
use Zstate\Crawler\Storage\History;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;
use Zstate\Crawler\Storage\Adapter\SqliteDsn;

class HistoryTest extends PHPUnit_Framework_TestCase
{
    private $adapter;

    public function setUp()
    {
        parent::setUp();

        $this->adapter = new SqliteAdapter(new SqliteDsn('sqlite::memory:'));
        $storageService = new StorageService($this->adapter);
        $storageService->importFile(__DIR__ . '/../../Storage/Schema/main.sql');

    }

    public function tearDown()
    {
        $this->adapter = null;

        parent::tearDown();
    }

    public function testAddAndContains()
    {
        $history = new History($this->adapter);

        $request = new Request('GET', '/test.html');
        $history->add($request);

        $this->assertTrue($history->contains($request));
        $this->assertFalse($history->contains(new Request('POST', '/test.html')));
    }
}
