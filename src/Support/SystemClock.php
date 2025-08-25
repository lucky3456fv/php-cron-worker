<?php
declare(strict_types=1);

namespace App\Support;

use App\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }

    public function sleep(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }
}

