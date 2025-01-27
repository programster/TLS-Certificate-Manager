<?php

use Programster\PgsqlObjects\AbstractTable;

class AuthTokenAssignmentRecord extends \Programster\PgsqlObjects\AbstractTableRowObject
{
    private string $m_authTokenId;
    private string $m_certificateBundleId;


    /**
     * Creates a new CertificateBundle record
     * WARNING - this does not automatically persist to the database. You need to call save or perform a bulk insert.
     * @param AuthTokenRecord|string $authToken - the auth token, or the ID of the auth token.
     * @param CertificateBundleRecord|string $certificateBundle - the certificate bundle, or the ID of the cert bundle.
     * @return AuthTokenAssignmentRecord
     * @throws ExceptionValidationFailed
     * @throws \Programster\PgsqlObjects\Exceptions\ExceptionMissingRequiredData
     */
    public static function createNew(
        AuthTokenRecord|string $authToken,
        CertificateBundleRecord|string $certificateBundle,
    ) : AuthTokenAssignmentRecord
    {
        $authTokenId = (is_string($authToken)) ? $authToken : $authToken->getId();
        $certBundleId = (is_string($certificateBundle)) ? $certificateBundle : $certificateBundle->getId();

        /* @var $tableHandler AuthTokenAssignmentTable */
        $tableHandler = AuthTokenAssignmentTable::getInstance();

        $wherePairs = [
            'auth_token_id' => $authTokenId,
            'certificate_bundle_id' => $certBundleId,
        ];

        $existingRecords = $tableHandler->loadWhereAnd($wherePairs);

        if (count($existingRecords) > 0)
        {
            throw new ExceptionValidationFailed("That authentication token has already been assigned to that certificate bundle.");
        }

        return self::createNewFromArray([
            'id' => AuthTokenAssignmentTable::getInstance()->generateId(),
            'auth_token_id' => $authTokenId,
            'certificate_bundle_id' => $certBundleId,
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
