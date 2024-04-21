<?php

require_once(__DIR__ . '/vendor/autoload.php');


$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->overload('/.env');

$requiredEnvVars = array(
    "ENVIRONMENT",
);

foreach ($requiredEnvVars as $requiredEnvVar)
{
    if (getenv($requiredEnvVar) === false)
    {
        throw new Exception("Required environment variable not set: " . $requiredEnvVar);
    }
}

define('ENVIRONMENT', getenv('ENVIRONMENT'));

$autoloader = new \iRAP\Autoloader\Autoloader([
    __DIR__,
    __DIR__ . "/controllers",
    __DIR__ . "/exceptions",
    __DIR__ . "/libs",
    __DIR__ . "/middleware",
    __DIR__ . "/models",
]);
