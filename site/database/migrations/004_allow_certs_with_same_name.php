<?php

use PgSql\Connection;
use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlMigrations\MigrationInterface;

class AllowCertsWithSameName implements MigrationInterface
{
    public function up(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);

        $queries[] = "ALTER TABLE certificate_bundle DROP CONSTRAINT \"certificate_bundle_name_key\"";

        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }


    public function down(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);
        $queries[] = 'ALTER TABLE certificate_bundle ADD CONSTRAINT "certificate_bundle_name_key" UNIQUE (name)';


        foreach ($queries as $query)
        {
            $db->query($query);
        }
    }
}
