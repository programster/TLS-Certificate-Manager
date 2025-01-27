<?php

# A script for quickly generating the bearer token in it's base64 and hashed form.

require_once(__DIR__ . '/../vendor/autoload.php');

use Programster\CoreLibs\StringLib;

$token = random_bytes(24);

echo "Base64 encoded bearer token is: " . base64_encode($token) . PHP_EOL;
echo "Hashed form for storing in the config is: " . password_hash($token, PASSWORD_DEFAULT) . PHP_EOL;
