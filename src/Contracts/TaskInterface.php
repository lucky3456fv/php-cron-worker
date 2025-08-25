<?php
declare(strict_types=1);

namespace App\Contracts;

interface TaskInterface
{
    public function getName(): string;

    // Interval in seconds between runs (e.g., 60, 300)
    public function getIntervalSeconds(): int;

    // Execute the task logic for a specific domain
    public function run(string $domain): void;
}

