<?php

use Programster\PgsqlMigrations\MigrationManager;

require_once(__DIR__ . '/../bootstrap.php');

$migrationManager = new MigrationManager(__DIR__ . '/../database/migrations', ServiceLib::getDb()->getResource());
$migrationManager->migrate();
