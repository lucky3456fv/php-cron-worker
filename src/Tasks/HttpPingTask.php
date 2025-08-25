<?php
declare(strict_types=1);

namespace App\Tasks;

use App\Contracts\TaskInterface;

final class HttpPingTask implements TaskInterface
{
    public function __construct(
        private readonly int $intervalSeconds = 60,
        private readonly string $path = '/'
    ) {}

    public function getName(): string
    {
        return 'http_ping';
    }

    public function getIntervalSeconds(): int
    {
        return $this->intervalSeconds;
    }

    public function run(string $domain): void
    {
        $url = 'https://' . $domain . $this->path;
        $status = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_NOBODY => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'DomainCronWorker/1.0',
            ]);
            curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $status = $err ? 'ERROR: ' . $err : 'HTTP ' . $code;
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                    'ignore_errors' => true,
                    'header' => "User-Agent: DomainCronWorker/1.0\r\n",
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $headers = @get_headers($url, false, $context);
            if ($headers === false) {
                $status = 'ERROR: get_headers failed';
            } else {
                $first = is_array($headers) && isset($headers[0]) ? (string) $headers[0] : '';
                $status = $first !== '' ? $first : 'OK';
            }
        }
        fwrite(STDOUT, sprintf("[%s] %s %s => %s\n", date('c'), $domain, $this->getName(), $status));
    }
}

