<?php

/*
 * This script is really simple, it keeps running until we can get a database connection.
 * This allows us to block startup of the site until the database is up and running.
 */

require_once(__DIR__ . '/../bootstrap.php');


$connected = false;

while ($connected === false)
{
    try
    {
        $db = @\Programster\PgsqlLib\PgSqlConnection::create(
            DB_HOST,
            DB_NAME,
            DB_USER,
            DB_PASSWORD
        );

        $connected = true;
    }
    catch (Exception)
    {
        print "Database still warming up. Waiting for it to come online..." . PHP_EOL;

        // wait for a bit to give the database time to spin up. No point wasting CPU.
        sleep(1);
    }
}



