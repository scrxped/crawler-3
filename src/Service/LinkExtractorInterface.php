<?php
declare(strict_types=1);

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
