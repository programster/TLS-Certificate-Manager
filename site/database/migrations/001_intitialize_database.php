<?php

use PgSql\Connection;
use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlMigrations\MigrationInterface;

class InitializeDatabase implements MigrationInterface
{
    public function up(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);
        $this->createCertificateBundlesTable($db);
        $this->createCertificateAuthTokensTable($db);
        $this->createAuthTokenAssignmentsTable($db);
    }


    private function createCertificateBundlesTable(PgSqlConnection $db)
    {
        $query = "
            CREATE TABLE certificate_bundle (
                id UUID NOT NULL PRIMARY KEY ,
                name VARCHAR(255) NOT NULL UNIQUE,
                fullchain TEXT NOT NULL,
                private_key text NOT NULL
            );
        ";

        $db->query($query);
    }

    private function createCertificateAuthTokensTable(PgSqlConnection $db)
    {
        $query = "
            CREATE TABLE auth_token (
                id UUID NOT NULL PRIMARY KEY,
                name varchar(50) NOT NULL UNIQUE,
                description varchar(255) NOT NULL,
                token_hash varchar(255) NOT NULL
            );
        ";

        $db->query($query);
    }


    private function createAuthTokenAssignmentsTable(PgSqlConnection $db)
    {
        $query = "
            CREATE TABLE auth_token_assignment (
                id UUID NOT NULL PRIMARY KEY,
                auth_token_id UUID NOT NULL REFERENCES auth_token(id) ON DELETE CASCADE ON UPDATE CASCADE,
                certificate_bundle_id UUID NOT NULL references certificate_bundle(id) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE (auth_token_id, certificate_bundle_id)
            );
        ";

        $db->query($query);
    }


    public function down(Connection $connectionResource): void
    {
        $db = new PgSqlConnection($connectionResource);
        $db->query("DROP TABLE auth_token_assignment");
        $db->query("DROP TABLE auth_token");
        $db->query("DROP TABLE certificate_bundle");
    }
}
