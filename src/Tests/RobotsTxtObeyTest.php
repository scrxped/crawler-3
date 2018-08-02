<?php
/**
 * Created by PhpStorm.
 * User: zubkevich
 * Date: 5/16/18
 * Time: 4:49 PM
 */

namespace Zstate\Crawler\Tests;

use PHPUnit\Framework\TestCase;
use Zstate\Crawler\Client;
use Zstate\Crawler\Tests\Middleware\HistoryMiddleware;

class RobotsTxtObeyTest extends TestCase
{
    public function testRobotsTxt()
    {
        $config = [
            'start_uri' => ['http://site1.local/robotstxt.html'],
            'concurrency' => 1,
            'request_options' => [
                'debug' => false,
            ],
            'filter' => [
                'robotstxt_obey' => true
            ]
        ];
        $client = new Client($config);

        $history = new HistoryMiddleware;

        $client->addRequestMiddleware($history);

        $client->run();

        $expected = [
            'GET http://site1.local/robotstxt.html',
            'GET http://site1.local/deny/this-is-allowed.html'
        ];

        $this->assertEquals($expected, $history->getHistory());
    }
}
