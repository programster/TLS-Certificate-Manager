<?php

use Programster\PgsqlObjects\AbstractTable;

class AuthTokenAssignmentRecord extends \Programster\PgsqlObjects\AbstractTableRowObject
{
    private string $m_authTokenId;
    private string $m_certificateBundleId;


    /**
     * Creates a new CertificateBundle record
     * WARNING - this does not automatically persist to the database. You need to call save or perform a bulk insert.
     * @param string $id
     * @param string $name
     * @param string $fullchain
     * @param string $privateKey
     * @return CertificateBundleRecord
     * @throws \Programster\PgsqlObjects\Exceptions\ExceptionMissingRequiredData
     */
    public static function createNew(
        AuthTokenRecord $authToken,
        CertificateBundleRecord $certificateBundle,
    )
    {
        /* @var $tableHandler AuthTokenAssignmentTable */
        $tableHandler = AuthTokenAssignmentTable::getInstance();

        $wherePairs = [
            'auth_token_id' => $authToken->getId(),
            'certificate_bundle_id' => $certificateBundle->getId(),
        ];

        $existingRecords = $tableHandler->loadWhereAnd($wherePairs);

        if (count($existingRecords) > 0)
        {
            throw new ExceptionValidationFailed("That authentication token has already been assigned to that certificate bundle.");
        }

        return self::createNewFromArray([
            'id' => AuthTokenAssignmentTable::getInstance()->generateId(),
            'auth_token_id' => $authToken->getId(),
            'certificate_bundle_id' => $certificateBundle->getId(),
        ]);
    }


    public function getTableHandler(): AbstractTable
    {
        return AuthTokenAssignmentTable::getInstance();
    }


    protected function getAccessorFunctions(): array
    {
        return [
            'auth_token_id' => function() : string { return $this->m_authTokenId; },
            'certificate_bundle_id' => function() : string { return $this->m_certificateBundleId; },
        ];
    }


    protected function getSetFunctions(): array
    {
        return [
            'auth_token_id' => function(string $x) { $this->m_authTokenId = $x; },
            'certificate_bundle_id' => function(string $x) { $this->m_certificateBundleId = $x; },
        ];
    }


    # Accessors
    public function getCertificateBundleId(): string { return $this->m_certificateBundleId; }
    public function getAuthTokenId(): string { return $this->m_authTokenId; }
}
