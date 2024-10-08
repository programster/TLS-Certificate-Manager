<?php

require_once(__DIR__ . '/vendor/autoload.php');


$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->overload('/.env');


$requiredEnvVars = array(
    "ENVIRONMENT",
    "DB_USER",
    "DB_PASSWORD",
    "DB_NAME",
    "DB_HOST",
    "ADMIN_AUTH_TOKEN_HASH",
);

foreach ($requiredEnvVars as $requiredEnvVar)
{
    if (getenv($requiredEnvVar) === false)
    {
        throw new Exception("Required environment variable not set: " . $requiredEnvVar);
    }
}

define('ENVIRONMENT', getenv('ENVIRONMENT'));
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_NAME', getenv('DB_NAME'));
define('ADMIN_AUTH_TOKEN_HASH', base64_decode(getenv('ADMIN_AUTH_TOKEN_HASH')));


if (ENVIRONMENT === "dev" || ENVIRONMENT === "staging")
{
    error_reporting(E_ALL);
}
else
{
    error_reporting(0);
}

displayErrorDetails:

$autoloader = new \iRAP\Autoloader\Autoloader([
    __DIR__,
    __DIR__ . "/controllers",
    __DIR__ . "/exceptions",
    __DIR__ . "/libs",
    __DIR__ . "/middleware",
    __DIR__ . "/models",
    __DIR__ . "/models/orm",
]);
