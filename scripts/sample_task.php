<?php
declare(strict_types=1);

// Simple demo script. Accepts domain as argv[1]
$domain = $argv[1] ?? 'unknown-domain';
fwrite(STDOUT, sprintf("Sample script ran for %s at %s\n", $domain, date('c')));

