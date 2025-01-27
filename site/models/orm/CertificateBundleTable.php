<?php

use Programster\PgsqlLib\PgSqlConnection;

class CertificateBundleTable extends \Programster\PgsqlObjects\AbstractTable
{
    public function getObjectClassName(): string
    {
        return CertificateBundleRecord::class;
    }

    public function getDb(): PgSqlConnection
    {
        return ServiceLib::getDb();
    }

    public function getFieldsThatAllowNull(): array
    {
        return [];
    }

    public function getFieldsThatHaveDefaults(): array
    {
        return [];
    }

    public function getTableName(): string
    {
        return "certificate_bundle";
    }

    public function generateId(): mixed
    {
        return \Programster\PgsqlObjects\Utils::generateUuid();
    }

    public function isIdGeneratedInDatabase(): bool
    {
        return false;
    }


    /**
     * Fetches the certificate bundles that thie provided auth token has read access to.
     * This may retrieve certificates that the user can read, but not update (e.g. full read user)
     */
    public function fetchForAuthTokenReadAccess(AuthTokenRecord $authToken)
    {
        if ($authToken->getAccessLevel()->value >= AuthTokenLevel::FULL_READ)
        {
            $certificates = $this->loadAll();
        }
        else
        {
            $authTokenId = $authToken->getId();
            $subQuery = AuthTokenAssignmentTable::getInstance()->getSelectCertificateIdsForAuthTokenIdQuery($authTokenId);
            $query = "SELECT * FROM {$this->getEscapedTableName()} WHERE id IN($subQuery)";
            $result = $this->getDb()->query($query);
            $certificates = $this->convertPgResultToObjects($result);
        }

        return $certificates;
    }


    /**
     * Fetches the certificate bundles that were specifically assigne to an auth token.
     */
    public function fetchAssignedToAuthToken(AuthTokenRecord $authToken)
    {
        $authTokenId = $authToken->getId();
        $subQuery = AuthTokenAssignmentTable::getInstance()->getSelectCertificateIdsForAuthTokenIdQuery($authTokenId);
        $query = "SELECT * FROM {$this->getEscapedTableName()} WHERE id IN($subQuery)";
        $result = $this->getDb()->query($query);
        $certificates = $this->convertPgResultToObjects($result);
        return $certificates;
    }
}
