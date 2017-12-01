<?php

namespace Zstate\Crawler\Service;

use GuzzleHttp\Psr7\UriNormalizer;
use Psr\Http\Message\RequestInterface;

class RequestFingerprint
{

    /**
     * Return the request fingerprint.
     * The request fingerprint is a hash that uniquely identifies the resource the
     * request points to. For example, take the following two urls:
     * http://www.example.com/query?id=111&cat=222
     * http://www.example.com/query?cat=222&id=111
     * Even though those are two different URLs both point to the same resource
     * and are equivalent (ie. they should return the same response).
     * Another example are cookies used to store session ids. Suppose the
     * following page is only accesible to authenticated users:
     * http://www.example.com/members/offers.html
     * Lot of sites use a cookie to store the session id, which adds a random
     * component to the HTTP Request and thus should be ignored when calculating
     * the fingerprint.
     * For this reason, request headers are ignored by default when calculating
     * the fingeprint. If you want to include specific headers use the
     * include_headers argument, which is a list of Request headers to include.
     *
     *
     * @param RequestInterface $request
     * @param array $includeHeaders
     * @return string
     */
    public static function calculate(RequestInterface $request, array $includeHeaders = [])
    {
        $hashContext = hash_init('sha256');

        hash_update($hashContext, $request->getMethod());

        $uri = UriNormalizer::normalize($request->getUri(), UriNormalizer::PRESERVING_NORMALIZATIONS | UriNormalizer::SORT_QUERY_PARAMETERS);
        hash_update($hashContext, $uri);

        self::hashRequestBody($request, $hashContext);

        if (! empty($includeHeaders)) {
            self::hashRequestHeaders($request, $hashContext, $includeHeaders);
        }

        $hash = hash_final($hashContext);

        return $hash;
    }

    /**
     * @param RequestInterface $request
     * @param $hashContext
     */
    private static function hashRequestBody(RequestInterface $request, $hashContext)
    {
        $stream = $request->getBody();

        $position = $stream->tell();

        if ($position > 0) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            hash_update($hashContext, $stream->read(1048576));
        }

        // Return stream to the same position
        $stream->seek($position);
    }

    /**
     * @param RequestInterface $request
     * @param $hashContext
     * @param array $includeHeaders
     */
    private static function hashRequestHeaders(RequestInterface $request, $hashContext, array $includeHeaders)
    {
        $headers = $request->getHeaders();
        ksort($headers);

        foreach ($headers as $name => $values) {
            if (in_array($name, $includeHeaders)) {
                sort($values);
                hash_update($hashContext, $name . implode(", ", $values));
            }
        }
    }
}
