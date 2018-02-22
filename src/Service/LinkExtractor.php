<?php
declare(strict_types=1);

namespace Zstate\Crawler\Service;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Zstate\Crawler\Config\FilterOptions;

class LinkExtractor implements LinkExtractorInterface
{
    /**
     * @var array
     */
    private $deniedUriPatterns = [];

    /**
     * @var array
     */
    private $allowedUriPatterns = [];

    /**
     * @var array
     */
    private $allowedDomains = [];
    /**
     * @var FilterOptions
     */
    private $filterOptions;


    public function __construct(? FilterOptions $filterOptions)
    {
        if(null !== $filterOptions) {
            $this->allowedUriPatterns = $filterOptions->allow();
            $this->deniedUriPatterns = $filterOptions->deny();
            $this->allowedDomains = $filterOptions->allowDomains();
        }
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function extract(ResponseInterface $response): array
    {
        $stream = $response->getBody();

        $content =  (string) $stream;

        $stream->rewind();

        $crawler = new Crawler($content);

        $elements = $crawler->filterXPath('(//a | //area)');

        $uriCollection = [];

        if (! empty($elements)) {
            /** @var \DOMElement $element */
            foreach ($elements as $element) {
                $href = (string) $element->getAttribute('href');
                $uri = new Uri($href);

                //Ignore anchors
                if ($this->isAnchor($uri)) {
                    continue;
                }

                if ($this->isDomainAllowed($uri) && $this->isUriAllowed($uri)) {
                    $uriCollection[] = $href;
                }
            }
        }
        return $uriCollection;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    private function isDomainAllowed(UriInterface $uri): bool
    {
        if (empty($this->allowedDomains)) {
            return true;
        }


        if (Uri::isAbsolute($uri)) {
            return in_array($uri->getHost(), $this->allowedDomains);
        }

        return true;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    private function isUriAllowed(UriInterface $uri): bool
    {
        if (empty($this->deniedUriPatterns) && empty($this->allowedUriPatterns)) {
            return true;
        }

        foreach ($this->allowedUriPatterns as $pattern) {
            if ($this->isUriMatchedPattern($uri, $pattern)) {
                return true;
            } else {
                return false;
            }
        }

        foreach ($this->deniedUriPatterns as $pattern) {
            if ($this->isUriMatchedPattern($uri, $pattern)) {
                return false;
            }
        }

        return true;
    }

    private function isUriMatchedPattern(UriInterface $uri, string $pattern): bool
    {
        $pattern = preg_quote($pattern, '/');

        $match = preg_match("/" . $pattern . "/i", (string) $uri);

        if(false === $match) {
            throw new \InvalidArgumentException('Invalid pattern: ' . $pattern);
        }

        return (bool) $match;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    private function isAnchor(UriInterface $uri): bool
    {
        $link = $uri->__toString();
        if (isset($link[0]) && $link[0] === '#') {
            return true;
        }

        return false;
    }
}
