<?php
declare(strict_types=1);

namespace Zstate\Crawler\Service;


use Zstate\Crawler\Storage\Adapter\SqliteAdapter;

class StorageService
{
    /**
     * @var SqliteAdapter
     */
    private $sqliteAdapter;

    public function __construct(SqliteAdapter $sqliteAdapter)
    {
        $this->sqliteAdapter = $sqliteAdapter;
    }

    public function importFile(string $path): void
    {
        $data = file_get_contents($path);

        $queries = explode(";\n", $data);

        foreach ($queries as $query) {
            $this->sqliteAdapter->executeQuery($query);
        }
    }
}