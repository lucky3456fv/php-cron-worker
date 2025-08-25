<?php
declare(strict_types=1);

namespace App\Contracts;

interface ClockInterface
{
    public function now(): int; // unix timestamp seconds
    public function sleep(float $seconds): void;
}

