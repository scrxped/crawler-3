<?php

namespace Zstate\Crawler\Listener;


use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Service\StorageService;

class StorageCreator
{
    /**
     * @var StorageService
     */
    private $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function beforeEngineStarted(BeforeEngineStarted $event): void
    {
        $this->storageService->importFile(__DIR__ . '/../Storage/Schema/main.sql');
    }
}