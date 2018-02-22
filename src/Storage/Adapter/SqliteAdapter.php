<?php
declare(strict_types=1);

namespace Zstate\Crawler\Storage\Adapter;

use PDO;

class SqliteAdapter
{
    private $storage;

    public function __construct(SqliteDsn $dsn)
    {
        $this->storage = new PDO($dsn->value());
        $this->storage->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function create(string $memoryOrFilePath): self
    {
        return new self(SqliteDsn::fromString($memoryOrFilePath));
    }

    /**
     * @inheritdoc
     */
    public function executeQuery(string $query, array $data = []): bool
    {
        $prepared = $this->storage->prepare($query);

        return $prepared->execute($data);
    }

    /**
     * @inheritdoc
     */
    public function fetchAll(string $query, array $data = []): array
    {
        $prepared = $this->storage->prepare($query);

        $prepared->execute($data);

        return $prepared->fetchAll(PDO::FETCH_ASSOC);
    }

    public function beginTransaction()
    {
        $this->storage->beginTransaction();
    }

    public function commit()
    {
        $this->storage->commit();
    }
}