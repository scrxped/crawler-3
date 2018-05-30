<?php
declare(strict_types=1);


namespace Zstate\Crawler\Extension;


use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Zstate\Crawler\Event\RequestFailed;
use Zstate\Crawler\Event\ResponseReceived;

class ConsoleLogging extends Extension
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        $message = $this->getMessageFormatted($request, $statusCode);

        if($statusCode >= 400) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        $this->logger->debug("Request Headers: " . PHP_EOL . $this->getHeadersFormatted($request));
        $this->logger->debug("Response Headers: " . PHP_EOL . $this->getHeadersFormatted($response));
        $this->logger->debug("Response Body: " . PHP_EOL . $response->getBody());
    }

    private function getHeadersFormatted(MessageInterface $message): string
    {
        $headers = [];
        foreach ($message->getHeaders() as $name => $values) {
            $headers[] = "        " . $name . ": " . implode(", ", $values);
        }

        return join(PHP_EOL, $headers);
    }

    public function requestFailed(RequestFailed $event): void
    {
        $request = $event->getRequest();
        $reason = $event->getReason();

        $message = $request->getMethod() . " " . $request->getUri() . " " . $reason->getMessage();

        $this->logger->error($message);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseReceived::class => 'responseReceived',
            RequestFailed::class => 'requestFailed',
        ];
    }

    private function getMessageFormatted(RequestInterface $request, int $statusCode): string
    {
        $message = $request->getMethod() . " " . $request->getUri() . " " . $statusCode;

        return $message;
    }
}