<?php
declare(strict_types=1);

namespace App\Scheduler;

use App\Contracts\ClockInterface;
use App\Process\ProcessManager;

final class Scheduler
{
    /** @var JobQueue */
    private JobQueue $queue;

    public function __construct(
        private readonly ClockInterface $clock,
        private readonly ProcessManager $processManager
    ) {
        $this->queue = new JobQueue();
    }

    public function addJob(JobDescriptor $job): void
    {
        $this->queue->insert($job, $job->nextRunAt);
    }

    public function runLoop(int $tickSleepMs = 200): void
    {
        pcntl_async_signals(true);
        $shouldRun = true;
        pcntl_signal(SIGTERM, function () use (&$shouldRun): void { $shouldRun = false; });
        pcntl_signal(SIGINT, function () use (&$shouldRun): void { $shouldRun = false; });

        while ($shouldRun) {
            $now = $this->clock->now();

            // Reap finished children
            $this->processManager->reap();

            // Try to dispatch ready jobs
            $dispatched = false;
            $pending = [];
            while (!$this->queue->isEmpty()) {
                /** @var JobDescriptor $job */
                $job = $this->queue->extract();
                if ($job->nextRunAt > $now) {
                    $pending[] = $job; // not ready yet
                    break;
                }
                if ($this->processManager->canSpawn($job->domain)) {
                    $this->processManager->spawn($job);
                    $job->nextRunAt = $now + max(1, $job->task->getIntervalSeconds());
                    $this->queue->insert($job, $job->nextRunAt);
                    $dispatched = true;
                } else {
                    // Cannot spawn now; requeue without delay to attempt soon
                    $pending[] = $job;
                    break;
                }
            }
            // push back any pending items
            foreach ($pending as $job) {
                $this->queue->insert($job, $job->nextRunAt);
            }

            if (!$dispatched) {
                $this->clock->sleep($tickSleepMs / 1000);
            }
        }
    }
}

