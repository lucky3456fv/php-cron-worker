<?php
declare(strict_types=1);

namespace App\Process;

use App\Scheduler\JobDescriptor;
use App\Contracts\ClockInterface;

final class ProcessManager
{
    /** @var array<int, array{domain:string, task:string, started_at:int}> */
    private array $children = [];

    public function __construct(
        private readonly ClockInterface $clock,
        private readonly int $maxGlobalProcesses = 50,
        private readonly int $maxPerDomainProcesses = 2
    ) {}

    public function canSpawn(string $domain): bool
    {
        if (count($this->children) >= $this->maxGlobalProcesses) {
            return false;
        }
        $perDomain = 0;
        foreach ($this->children as $child) {
            if ($child['domain'] === $domain) {
                $perDomain++;
            }
        }
        return $perDomain < $this->maxPerDomainProcesses;
    }

    public function spawn(JobDescriptor $job): void
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \RuntimeException('Failed to fork');
        }
        if ($pid === 0) {
            // Child process
            $this->runChild($job);
            exit(0);
        }
        $this->children[$pid] = [
            'domain' => $job->domain,
            'task' => $job->task->getName(),
            'started_at' => $this->clock->now(),
        ];
    }

    private function runChild(JobDescriptor $job): void
    {
        // Isolate signals
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function (): void {
            exit(0);
        });
        $job->task->run($job->domain);
    }

    public function reap(): void
    {
        // Non-blocking wait for any child
        while (true) {
            $status = 0;
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
            if ($pid <= 0) {
                break;
            }
            unset($this->children[$pid]);
        }
    }

    public function activeCount(): int
    {
        return count($this->children);
    }
}

