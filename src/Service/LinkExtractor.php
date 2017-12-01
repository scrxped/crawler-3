<?php
namespace Zstate\Crawler\Service;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;

class LinkExtractor implements LinkExtractorInterface
{
    /**
     * @var array
     */
    private $deniedUriPatterns = [];

    /**
     * @var array
     */
    private $allowedDomains = [];

    /**
     * @param array $config
     * @return LinkExtractor
     */
    public static function fromConfig(array $config)
    {
        $extractor = new self;

        if (! empty($config['deny'])) {
            $extractor->deny($config['deny']);
        }

        if (! empty($config['allow_domains'])) {
            $extractor->allowDomains($config['allow_domains']);
        }

        return $extractor;
    }

    /**
     * @param array $deniedUriPatterns links
     * @return LinkExtractor
     */
    public function deny(array $deniedUriPatterns)
    {
        $this->deniedUriPatterns = $deniedUriPatterns;

        return $this;
    }

    /**
     * @param array $allowedDomains
     * @return LinkExtractor
     */
    public function allowDomains(array $allowedDomains)
    {
        $this->allowedDomains = $allowedDomains;

        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function extract(ResponseInterface $response)
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
    private function isDomainAllowed(UriInterface $uri)
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
    private function isUriAllowed(UriInterface $uri)
    {
        if (empty($this->deniedUriPatterns)) {
            return true;
        }

        foreach ($this->deniedUriPatterns as $pattern) {
            $match = preg_match("/" . $pattern . "/i", $uri->__toString());

            //An error in pattern
            if (false === $match) {
                throw new \InvalidArgumentException('Invalid pattern.');
            } elseif (1 === $match) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    private function isAnchor(UriInterface $uri)
    {
        $link = $uri->__toString();
        if (isset($link[0]) && $link[0] === '#') {
            return true;
        }

        return false;
    }
}
