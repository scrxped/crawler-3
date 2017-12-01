<?php
namespace Zstate\Crawler\Service;

use Psr\Http\Message\ResponseInterface;

interface LinkExtractorInterface
{
    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    public function extract(ResponseInterface $response);
}
