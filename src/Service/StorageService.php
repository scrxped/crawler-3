<?php
declare(strict_types=1);

namespace Zstate\Crawler\Service;


use Zstate\Crawler\Storage\Adapter\SqliteAdapter;

/**
 * @package Zstate\Crawler\Service
 */
class StorageService
{
    /**
     * @var SqliteAdapter
     */
    private $sqliteAdapter;

    /**
     * @param SqliteAdapter $sqliteAdapter
     */
    public function __construct(SqliteAdapter $sqliteAdapter)
    {
        $this->sqliteAdapter = $sqliteAdapter;
    }

    /**
     * @param string $path
     */
    public function importFile(string $path): void
    {
        $data = file_get_contents($path);

        $queries = explode(";\n", $data);

        foreach ($queries as $query) {
            $this->sqliteAdapter->executeQuery($query);
        }
    }
}