<?php
$file = $argv[1] ?? 'tmp/output.txt';
echo file_get_contents($file);
