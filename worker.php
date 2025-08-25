<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Support\SystemClock;
use App\Process\ProcessManager;
use App\Scheduler\{Scheduler, JobDescriptor};
use App\Tasks\{HttpPingTask, PhpScriptTask};

// CLI options
$opts = getopt('', [
    'config::',
    'max-procs::',
    'max-per-domain::',
    'tick-ms::',
]);

$configFile = $opts['config'] ?? __DIR__ . '/config/domains.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Config file not found: {$configFile}\n");
    exit(1);
}

/** @var array<string, array<int, array<string, mixed>>> $domainConfig */
$domainConfig = require $configFile;

$clock = new SystemClock();
$processManager = new ProcessManager(
    $clock,
    (int) ($opts['max-procs'] ?? 100),
    (int) ($opts['max-per-domain'] ?? 2)
);
$scheduler = new Scheduler($clock, $processManager);

// Build tasks per domain
foreach ($domainConfig as $domain => $tasks) {
    foreach ($tasks as $taskDef) {
        $type = $taskDef['type'] ?? '';
        $interval = (int) ($taskDef['interval'] ?? 60);
        $task = match ($type) {
            'http_ping' => new HttpPingTask($interval, (string) ($taskDef['path'] ?? '/')),
            'php_script' => new PhpScriptTask((string) $taskDef['script'], $interval),
            default => null,
        };
        if ($task === null) {
            fwrite(STDERR, sprintf("Unknown task type '%s' for domain %s\n", (string)$type, (string)$domain));
            continue;
        }
        $scheduler->addJob(new JobDescriptor($domain, $task, time()));
    }
}

$tick = (int) ($opts['tick-ms'] ?? 200);
fwrite(STDOUT, sprintf("Starting worker with %d domains. Tick=%dms\n", count($domainConfig), $tick));
$scheduler->runLoop($tick);

