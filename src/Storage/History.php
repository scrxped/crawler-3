<?php

namespace Zstate\Crawler\Storage;


use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;

class History implements HistoryInterface
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
            CREATE TABLE IF NOT EXISTS history (
                fingerprint TEXT PRIMARY KEY 
            )
        ');
    }

    public function contains(RequestInterface $request): bool
    {
        $fingerprint=RequestFingerprint::calculate($request);

        $result = $this->storageAdapter->fetchAll(
            'SELECT `fingerprint` FROM `history` WHERE `fingerprint`=? LIMIT 1',[$fingerprint]
        );

        if(empty($result)) {
            return false;
        }

        return true;
    }

    public function add(RequestInterface $request): void
    {
        $fingerprint = RequestFingerprint::calculate($request);
        $this->storageAdapter->executeQuery(
            'INSERT OR IGNORE INTO `history` (`fingerprint`) VALUES (?)', [$fingerprint]
        );
    }
}