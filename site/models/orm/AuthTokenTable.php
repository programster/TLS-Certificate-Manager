<?php

use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlObjects\Utils;

class AuthTokenTable extends \Programster\PgsqlObjects\AbstractTable
{

    public function getObjectClassName(): string
    {
        return AuthTokenRecord::class;
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
        return "auth_token";
    }

    public function generateId(): mixed
    {
        return Utils::generateUuid();
    }

    public function isIdGeneratedInDatabase(): bool
    {
        return false;
    }


    /**
     * Fetches the auth tokens that relate to a given certificate.
     * @param CertificateBundleRecord|int $certificateBundle
     * @return \Programster\PgsqlObjects\AbstractTableRowObject[]
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionQueryError
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionUnexpectedValueType
     */
    public function fetchForCertificate(CertificateBundleRecord|int $certificateBundle)
    {
        $certificateId = (is_string($certificateBundle)) ? $certificateBundle : $certificateBundle->getId();
        $subQuery = AuthTokenAssignmentTable::getInstance()->getSelectAuthTokenIdsForCertificateIdQuery($certificateId);
        $query = "SELECT * FROM {$this->getEscapedTableName()} WHERE id IN($subQuery)";
        $result = $this->getDb()->query($query);
        return $this->convertPgResultToObjects($result);
    }
}
