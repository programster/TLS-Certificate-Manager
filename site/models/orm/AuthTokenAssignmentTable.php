<?php

use Programster\PgsqlLib\PgSqlConnection;
use Programster\PgsqlObjects\Utils;

class AuthTokenAssignmentTable extends \Programster\PgsqlObjects\AbstractTable
{

    public function getObjectClassName(): string
    {
        return AuthTokenAssignmentRecord::class;
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
        return "auth_token_assignment";
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
     * Generates the query what will return the IDs of all of the auth tokens that are assigned to a certificate.
     * This returns the query, not the result. This is useful for nested queries.
     * @param string $certificateId
     * @return string
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionUnexpectedValueType
     */
    public function getSelectAuthTokenIdsForCertificateIdQuery(string $certificateId)
    {
        $escapedCertificateId = $this->getDb()->escapeValue($certificateId);
        $escapedColumnName = $this->getDb()->escapeIdentifier("certificate_bundle_id");
        return "SELECT auth_token_id FROM {$this->getEscapedTableName()} WHERE {$escapedColumnName} = {$escapedCertificateId}";
    }


    /**
     * Generates the query what will return the IDs of all of the certificatesthat are assigned to an auth token.
     * This returns the query, not the result. This is useful for nested queries.
     * @param string $authTokenId
     * @return string
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionUnexpectedValueType
     */
    public function getSelectCertificateIdsForAuthTokenIdQuery(string $authTokenId)
    {
        $escapedAuthTokenId = $this->getDb()->escapeValue($authTokenId);
        $escapedColumnName = $this->getDb()->escapeIdentifier("auth_token_id");
        return "SELECT certificate_bundle_id FROM {$this->getEscapedTableName()} WHERE {$escapedColumnName} = {$escapedAuthTokenId}";
    }
}
