<?php

use Programster\PgsqlLib\PgSqlConnection;

class ServiceLib
{
    public static function getDb() : PgSqlConnection
    {
        static $db = null;

        if ($db === null)
        {
            $db = PgSqlConnection::create(
                DB_HOST,
                DB_NAME,
                DB_USER,
                DB_PASSWORD
            );
        }

        return $db;
    }
}
