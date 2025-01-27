<?php

class AuthTokenRecord extends \Programster\PgsqlObjects\AbstractTableRowObject
{
    private string $m_name;
    private string $m_description;
    private AuthTokenLevel $m_accessLevel;
    private string $m_tokenHash;


    /**
     * Creates a new auth token record
     * WARNING - this does not automatically persist to the database. You need to call save or perform a bulk insert.
     * @param string $id
     * @param string $name
     * @param string $token - the raw token. No base64 encoding or anything.
     * @param AuthTokenLevel $accessLevel
     * @param string|null $description
     * @return AuthTokenRecord
     * @throws \Programster\PgsqlObjects\Exceptions\ExceptionMissingRequiredData
     */
    public static function createNew(
        string $id,
        string $name,
        string $token,
        AuthTokenLevel $accessLevel,
        ?string $description
    ) : AuthTokenRecord
    {
        return self::createNewFromArray([
            'id' => $id,
            'name' => $name,
            'level' => $accessLevel->value,
            'description' => $description ?? "",
            'token_hash' => password_hash($token, PASSWORD_DEFAULT),
        ]);
    }


    /**
     * Verify that the provided secret was valid for this token.
     * @param string $base64EncodedToken
     * @return void
     * @throws ExceptionVerificationFailed - if verification fails
     */
    public function verify(string $base64EncodedToken) : void
    {
        $decodedForm = base64_decode($base64EncodedToken);

        if (password_verify($decodedForm, $this->m_tokenHash) === false)
        {
            throw new ExceptionVerificationFailed();
        };
    }


    public function getTableHandler(): \Programster\PgsqlObjects\TableInterface
    {
        return AuthTokenTable::getInstance();
    }

    protected function getAccessorFunctions(): array
    {
        return [
            'name' => function() : string { return $this->m_name; },
            'description' => function() : string { return $this->m_description; },
            'level' => function() : int { return $this->m_accessLevel->value; },
            'token_hash' => function() : string { return $this->m_tokenHash; },
        ];
    }

    protected function getSetFunctions(): array
    {
        return [
            'name' => function(string $x) { $this->m_name = $x; },
            'description' => function(string $x) { $this->m_description = $x; },
            'level' => function(int $x) { $this->m_accessLevel = AuthTokenLevel::from($x); },
            'token_hash' => function(string $x) { $this->m_tokenHash = $x; },
        ];
    }


    public function fetchCertificates() : array
    {
        return CertificateBundleTable::getInstance()->fetchForAuthTokenReadAccess($this);
    }


    public function getPublicArrayForm() : array
    {
        return [
            'id' => $this->m_id,
            'name' => $this->m_name,
            'level' => $this->m_accessLevel->value,
            'description' => $this->m_description,
        ];
    }


    public function isAdmin() : bool
    {

        if ($this->getAccessLevel() === AuthTokenLevel::ADMIN)
        {
            return true;
        }
        else
        {
            die(print_r($this->getAccessLevel(), true));
            return false;
        }
    }

    public function isCreator() : bool
    {
        return ($this->getAccessLevel() === AuthTokenLevel::CERTIFICATE_CREATOR);
    }

    public function isFullReader() : bool
    {
        return ($this->getAccessLevel() === AuthTokenLevel::FULL_READ);
    }

    public function isNormal() : bool
    {
        return ($this->getAccessLevel() === AuthTokenLevel::NORMAL);
    }


    public function hasReadCertificatePermission(CertificateBundleRecord $record) : bool
    {
        return (
               $this->isAdmin()
            || $this->isFullReader()
            || (AuthTokenAssignmentTable::getInstance()->isAssigned($this->getId(), $record->getId()))
        );
    }

    public function hasUpdateCertificatePermission(CertificateBundleRecord $record) : bool
    {
        return (
            $this->isAdmin()
            || ($this->isCreator() && AuthTokenAssignmentTable::getInstance()->isAssigned($this->getId(), $record->getId()))
        );
    }

    public function hasDeleteCertificatePermission(CertificateBundleRecord $record) : bool
    {
        return (
                $this->isAdmin()
            || ($this->isCreator() && AuthTokenAssignmentTable::getInstance()->isAssigned($this->getId(), $record->getId()))
        );
    }


    # Accessors
    public function getName() : string { return $this->m_name; }
    public function getDescription() : string { return $this->m_description; }
    public function getTokenHash() : string { return $this->m_tokenHash; }
    public function getAccessLevel() : AuthTokenLevel { return $this->m_accessLevel; }
}
