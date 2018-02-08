<?php

namespace Zstate\Crawler\Tests\Service;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Zstate\Crawler\Service\LinkExtractor;

class LinkExtractorTest extends TestCase
{
    public function testLinkExtractorFromConfig()
    {
        $extractor = LinkExtractor::fromConfig([
            'deny' => ['\/logout'],
            'allow_domains' => ['test.com']
        ]);

        $response = new Response(
            200,
            [],
            '<a href="/test">test</a>'
            . '<a href="http://www.test.com/test1">test</a>'
            . '<a href="http://test.com/test2">test</a>'
            . '<a href="http://otherdomain.com/otherdomain">otherdomain</a>'
            . '<a href="/logout">logout</a>'
        );

        $links = $extractor->extract($response);

        $this->assertEquals(['/test', 'http://test.com/test2'], $links);
    }

    public function testIgnoreAnchors()
    {
        $extractor = LinkExtractor::fromConfig([
            'allow_domains' => ['test.com']
        ]);

        $response = new Response(
            200,
            [],
            '<a href="/test">test</a>'
            . '<a href="/test#someAnchor">test#someAnchor</a>'
            . '<a href="#someAnchor">someAnchor</a>'
            . '<a href="/logout">logout</a>'
        );

        $links = $extractor->extract($response);

        $this->assertEquals(['/test', '/test#someAnchor', '/logout'], $links);
    }
}
