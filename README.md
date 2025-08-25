### PHP Domain Cron Worker (OOP, Forking Scheduler)

This worker schedules and runs up to N cron-like tasks per domain using PHP CLI with pcntl forking. Each task is defined once and scheduled for each domain with its own interval.

#### Requirements
- PHP 8.1+ CLI
- pcntl and posix extensions enabled

#### Structure
- `bootstrap.php`: PSR-4 autoloader and env checks
- `worker.php`: CLI entrypoint
- `config/domains.php`: Domains and their task definitions (intervals)
- `src/Contracts`: `TaskInterface`, `ClockInterface`
- `src/Tasks`: Example tasks `HttpPingTask`, `PhpScriptTask`
- `src/Scheduler`: `Scheduler`, `JobQueue`, `JobDescriptor`
- `src/Process`: `ProcessManager`

#### Configure domains and tasks
Edit `config/domains.php`:

```php
return [
  'domain-a.com' => [
    [ 'type' => 'http_ping', 'interval' => 60, 'path' => '/' ],
    [ 'type' => 'php_script', 'interval' => 300, 'script' => __DIR__ . '/../scripts/sample_task.php' ],
  ],
  'domain-b.com' => [
    [ 'type' => 'http_ping', 'interval' => 300 ],
  ],
];
```

Supported `type` values:
- `http_ping`: options `interval`, `path`
- `php_script`: options `interval`, `script`

#### Run

```bash
php /workspace/worker.php --config=/workspace/config/domains.php \
  --max-procs=100 --max-per-domain=2 --tick-ms=200
```

Stop with Ctrl+C or send SIGTERM to the process.

#### Notes
- Scheduler ensures per-domain concurrency limit and global max processes.
- Each job re-schedules itself after it runs based on its `interval`.
- Extend by implementing `App\Contracts\TaskInterface` and mapping in `worker.php`.

# php-cron-worker