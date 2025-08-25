<?php
declare(strict_types=1);

namespace App\Scheduler;

/**
 * Min-heap priority queue ordered by nextRunAt timestamp.
 */
final class JobQueue extends \SplPriorityQueue
{
    public function compare($priority1, $priority2): int
    {
        // SplPriorityQueue extracts highest priority first.
        // We want the earliest nextRunAt first, so invert sign.
        return $priority2 <=> $priority1;
    }
}

