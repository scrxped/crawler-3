<?php
declare(strict_types=1);


namespace Zstate\Crawler\Middleware;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use webignition\RobotsTxt\File\Parser;
use webignition\RobotsTxt\Inspector\Inspector;
use Zstate\Crawler\Exception\InvalidRequestException;

/**
 * @package Zstate\Crawler\Middleware
 */
class RobotsTxtMiddleware implements RequestMiddleware
{
    private const USER_AGENT = 'zstate/crawler';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct()
    {
        $this->client = new Client;
    }

    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request): RequestInterface
    {
        $robotTxtUri = (string) $request->getUri()->withPath('/robots.txt');

        // Checking cache first
        $robotTxtContent = "";
        if (empty($this->cache[$robotTxtUri])) {
            // Getting content of the robots.txt file
            $robotTxtResponse = $this->client->request('GET', $robotTxtUri);

            // Robots.txt file exists
            if ($robotTxtResponse->getStatusCode() === 200) {
                $robotTxtContent = (string) $robotTxtResponse->getBody();

                // Store the content of the robots.txt in the cache
                $this->cache[$robotTxtUri] = $robotTxtContent;
            }
        } else {
            $robotTxtContent = $this->cache[$robotTxtUri];
        }

        // If the content is still empty, then robots.txt doesn't exist or is empty (no rules). Go to the next middleware
        if (empty($robotTxtContent)) {
            return $request;
        }

        $inspector = $this->getInspector($robotTxtContent);

        // Go to the next middleware if it is allowed
        if ($inspector->isAllowed($request->getUri()->getPath())) {
            return $request;
        }

        // Stopping this request
        throw new InvalidRequestException('The path "' . $request->getUri()->getPath() . '" is not allowed by robots.txt.');
    }

    /**
     * @param string $robotTxtContent
     * @return Inspector
     */
    private function getInspector(string $robotTxtContent): Inspector
    {
        $parser = new Parser;
        $parser->setSource($robotTxtContent);

        $inspector = new Inspector($parser->getFile());
        $inspector->setUserAgent(self::USER_AGENT);

        return $inspector;
    }
}
