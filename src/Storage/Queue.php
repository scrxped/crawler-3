<?php

namespace Zstate\Crawler\Storage;


use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;

class Queue implements QueueInterface
{
    /**
     * @var SqliteAdapter
     */
    private $storageAdapter;

    public function __construct(SqliteAdapter $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
        $this->initialize();
    }

    private function initialize()
    {
        $this->storageAdapter->executeQuery('
            CREATE TABLE IF NOT EXISTS queue (
                fingerprint TEXT PRIMARY KEY,
                data TEXT 
            )
        ');
    }

    public function enqueue(RequestInterface $request): void
    {
        $fingerprint = RequestFingerprint::calculate($request);

        $data = $this->serialize($request);

        $this->storageAdapter->executeQuery(
            'INSERT OR IGNORE INTO `queue` (`fingerprint`,`data`) VALUES (?,?)', [$fingerprint, $data]
        );

    }

    public function dequeue(): RequestInterface
    {
        $this->storageAdapter->beginTransaction();

        $data = $this->storageAdapter->fetchAll('SELECT `fingerprint`,`data` FROM `queue` ORDER BY ROWID ASC LIMIT 1');

        $this->storageAdapter->executeQuery('DELETE FROM `queue` WHERE `fingerprint`=?', [$data[0]['fingerprint']]);

        $this->storageAdapter->commit();

        return $this->unserialize($data[0]['data']);
    }

    public function isEmpty(): bool
    {
        $data = $this->storageAdapter->fetchAll('SELECT fingerprint FROM `queue` LIMIT 1');

        if(empty($data)) {
            return true;
        }

        return false;

    }

    private function serialize(RequestInterface $request): string
    {
        $data = [
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
            'body' => (string) $request->getBody()
        ];

        return \GuzzleHttp\json_encode($data);

    }

    private function unserialize(string $jsonData): RequestInterface
    {
        $data = \GuzzleHttp\json_decode($jsonData,true);

        return new Request($data['method'], $data['uri'], $data['headers'], $data['body']);
    }



}