<?php

use PgSql\Connection;
use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlMigrations\MigrationInterface;

class AddAccessLevel implements MigrationInterface
{
    public function up(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);

        $queries[] = "ALTER TABLE auth_token ADD COLUMN \"level\" int default 1 NOT NULL";
        $queries[] = "ALTER TABLE auth_token ALTER COLUMN \"level\" DROP DEFAULT";

        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }



    public function down(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);
        $queries[] = "ALTER TABLE auth_token DROP COLUMN \"level\" ";

        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }
}
