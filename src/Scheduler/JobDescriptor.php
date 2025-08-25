<?php
declare(strict_types=1);

namespace App\Scheduler;

use App\Contracts\TaskInterface;

final class JobDescriptor
{
    public function __construct(
        public readonly string $domain,
        public readonly TaskInterface $task,
        public int $nextRunAt
    ) {}
}

