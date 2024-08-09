<?php

use Programster\PgsqlObjects\AbstractTableRowObject;

class CertificateBundleRecord extends AbstractTableRowObject
{
    private string $m_name;
    private string $m_fullchain;
    private string $m_privateKey;


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
    public static function createNew(string $id, string $name, string $fullchain, string $privateKey)
    {
        return self::createNewFromArray([
            'id' => $id,
            'name' => $name,
            'fullchain' => $fullchain,
            'private_key' => $privateKey
        ]);
    }

    public function getTableHandler(): \Programster\PgsqlObjects\TableInterface
    {
        return CertificateBundleTable::getInstance();
    }

    protected function getAccessorFunctions(): array
    {
        return [
            'name' => function() : string { return $this->m_name; },
            'fullchain' => function() : string { return $this->m_fullchain; },
            'private_key' => function() : string { return $this->m_privateKey; },
        ];
    }

    protected function getSetFunctions(): array
    {
        return [
            'name' => function(string $x) { $this->m_name = $x; },
            'fullchain' => function(string $x) { $this->m_fullchain = $x; },
            'private_key' => function(string $x) { $this->m_privateKey = $x; },
        ];
    }


    /**
     * Fetches the certificates that relate to this object
     * @return AbstractTableRowObject[]
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionQueryError
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionUnexpectedValueType
     */
    public function fetchAuthTokens()
    {
        return AuthTokenTable::getInstance()->fetchForCertificate($this);
    }


    # Accessors
    public function getName() : string { return $this->m_name; }
    public function getPrivateKey() : string { return $this->m_privateKey; }
    public function getFullchain() : string { return $this->m_fullchain; }

    # Setters
    public function setName(string $name) {$this->m_name = $name; }
    public function setPrivateKey(string $privateKey) {$this->m_privateKey = $privateKey; }
    public function setFullchain(string $fullchain) {$this->m_fullchain = $fullchain; }

}
