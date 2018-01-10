<?php
namespace Zstate\Crawler\Service;

use Psr\Http\Message\ResponseInterface;

interface LinkExtractorInterface
{
    /**
     * @param ResponseInterface $response
     * @return array
     */
    public function extract(ResponseInterface $response): array;
}
