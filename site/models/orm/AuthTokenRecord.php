<?php

class AuthTokenRecord extends \Programster\PgsqlObjects\AbstractTableRowObject
{
    private string $m_name;
    private string $m_description;
    private string $m_tokenHash;


    /**
     * Creates a new auth token record
     * WARNING - this does not automatically persist to the database. You need to call save or perform a bulk insert.
     * @param string $id
     * @param string $name
     * @param string $fullchain
     * @param string $privateKey
     * @throws \Programster\PgsqlObjects\Exceptions\ExceptionMissingRequiredData
     */
    public static function createNew(
        string $id,
        string $name,
        string $token,
        ?string $description
    ) : AuthTokenRecord
    {
        return self::createNewFromArray([
            'id' => $id,
            'name' => $name,
            'description' => $description ?? "",
            'token_hash' => password_hash($token, PASSWORD_DEFAULT),
        ]);
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
            'token_hash' => function() : string { return $this->m_tokenHash; },
        ];
    }

    protected function getSetFunctions(): array
    {
        return [
            'name' => function(string $x) { $this->m_name = $x; },
            'description' => function(string $x) { $this->m_description = $x; },
            'token_hash' => function(string $x) { $this->m_tokenHash = $x; },
        ];
    }


    public function fetchCertificates() : array
    {
        return CertificateBundleTable::getInstance()->fetchForAuthToken($this);
    }


    public function getPublicArrayForm() : array
    {
        return [
            'id' => $this->m_id,
            'name' => $this->m_name,
            'description' => $this->m_description,
        ];
    }


    # Accessors
    public function getName() : string { return $this->m_name; }
    public function getDescription() : string { return $this->m_description; }
    public function getTokenHash() : string { return $this->m_tokenHash; }
}
