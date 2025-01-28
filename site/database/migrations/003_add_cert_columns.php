<?php

use PgSql\Connection;
use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlMigrations\MigrationInterface;

class AddCertColumns implements MigrationInterface
{
    public function up(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);

        $queries[] = "ALTER TABLE certificate_bundle ADD COLUMN \"cert\" varchar default '' NOT NULL";
        $queries[] = "ALTER TABLE certificate_bundle ALTER COLUMN \"cert\" DROP DEFAULT";

        $queries[] = "ALTER TABLE certificate_bundle ADD COLUMN \"chain\" varchar default '' NOT NULL";
        $queries[] = "ALTER TABLE certificate_bundle ALTER COLUMN \"chain\" DROP DEFAULT";

        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }



    public function down(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);
        $queries[] = "ALTER TABLE certificate_bundle DROP COLUMN \"cert\" ";
        $queries[] = "ALTER TABLE certificate_bundle DROP COLUMN \"chain\" ";

        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }
}
