<?php

namespace Zstate\Crawler\Tests\Middleware;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use Zstate\Crawler\Middleware\RequestScheduler;
use Zstate\Crawler\Service\LinkExtractorInterface;

class RequestSchedulerTest extends \PHPUnit_Framework_TestCase
{
    public function testCanOnlyScheduleAbsoluteUrl()
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @var ClientInterface $httpClient */
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();

        /** @var LinkExtractorInterface $linkExtractor */
        $linkExtractor = $this->getMockBuilder(LinkExtractorInterface::class)->getMock();


        $scheduler = new RequestScheduler($httpClient, $linkExtractor);

        $scheduler->schedule(new Uri('/test'));
    }
}
