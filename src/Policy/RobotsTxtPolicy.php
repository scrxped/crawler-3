<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Psr\Http\Message\RequestInterface;
use webignition\RobotsTxt\File\Parser;
use webignition\RobotsTxt\Inspector\Inspector;

class RobotsTxtPolicy
{
    private const USER_AGENT = 'zstate/crawler';

    /**
     * @var string
     */
    private $robotTxtContent;

    /**
     * RobotsTxtPolicy constructor.
     * @param string $robotTxtContent
     */
    public function __construct(string $robotTxtContent)
    {
        $this->robotTxtContent = $robotTxtContent;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function isRequestAllowed(RequestInterface $request): bool
    {
        return $this->getInspector()->isAllowed($request->getUri()->getPath());
    }

    /**
     * @return Inspector
     */
    private function getInspector(): Inspector
    {
        $parser = new Parser;
        $parser->setSource($this->robotTxtContent);

        $inspector = new Inspector($parser->getFile());
        $inspector->setUserAgent(self::USER_AGENT);

        return $inspector;
    }
}