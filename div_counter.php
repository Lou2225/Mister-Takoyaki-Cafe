<?php
$content = file_get_contents('resources/views/livewire/branch-management.blade.php');
$openCount = preg_match_all('/<div[\s>]/', $content);
$closeCount = preg_match_all('/<\/div>/', $content);
echo "Open: $openCount, Close: $closeCount, Diff: " . ($openCount - $closeCount) . PHP_EOL;
