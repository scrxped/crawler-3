<?php
declare(strict_types=1);


namespace Zstate\Crawler\Event;

use GuzzleHttp\TransferStats;
use Symfony\Component\EventDispatcher\Event;

/**
 * @package Zstate\Crawler\Event
 */
class TransferStatisticReceived extends Event
{
    /**
     * @var TransferStats
     */
    private $transferStats;

    /**
     * @param TransferStats $transferStats
     */
    public function __construct(TransferStats $transferStats)
    {
        $this->transferStats = $transferStats;
    }

    /**
     * @return TransferStats
     */
    public function getTransferStats(): TransferStats
    {
        return $this->transferStats;
    }
}
