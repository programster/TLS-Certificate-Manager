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
     * Fetches the certificate bundles that relate to a given auth token.
     */
    public function fetchForAuthToken(AuthTokenRecord|int $authToken)
    {
        $authTokenId = (is_string($authToken)) ? $authToken : $authToken->getId();
        $subQuery = AuthTokenAssignmentTable::getInstance()->getSelectCertificateIdsForAuthTokenIdQuery($authTokenId);
        $query = "SELECT * FROM {$this->getEscapedTableName()} WHERE id IN($subQuery)";
        $result = $this->getDb()->query($query);
        return $this->convertPgResultToObjects($result);
    }
}
