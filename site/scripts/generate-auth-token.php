<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Programster\CoreLibs\StringLib;

$randomString = StringLib::generateRandomString(24);

echo "Bearer token is: " . base64_encode($randomString) . PHP_EOL;
echo "Hashed form for the config is: " . password_hash($randomString, PASSWORD_DEFAULT) . PHP_EOL;
