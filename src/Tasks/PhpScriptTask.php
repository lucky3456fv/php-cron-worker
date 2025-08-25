<?php
declare(strict_types=1);

namespace App\Tasks;

use App\Contracts\TaskInterface;

/**
 * Invokes a PHP script via CLI for the domain.
 * Your script should accept the domain as first arg.
 */
final class PhpScriptTask implements TaskInterface
{
    public function __construct(
        private readonly string $scriptPath,
        private readonly int $intervalSeconds
    ) {}

    public function getName(): string
    {
        return 'php_script:' . basename($this->scriptPath);
    }

    public function getIntervalSeconds(): int
    {
        return $this->intervalSeconds;
    }

    public function run(string $domain): void
    {
        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($this->scriptPath) . ' ' . escapeshellarg($domain);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptorSpec, $pipes);
        if (!\is_resource($process)) {
            fwrite(STDERR, sprintf("[%s] Failed to start script for %s: %s\n", date('c'), $domain, $cmd));
            return;
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        fwrite(STDOUT, sprintf("[%s] %s %s exit=%d\n", date('c'), $domain, $this->getName(), $exit));
        if ($out !== '') {
            fwrite(STDOUT, trim($out) . "\n");
        }
        if ($err !== '') {
            fwrite(STDERR, trim($err) . "\n");
        }
    }
}

