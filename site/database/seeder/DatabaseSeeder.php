<?php

class DatabaseSeeder
{
    public function run()
    {
        $db = ServiceLib::getDb();

        AuthTokenAssignmentTable::getInstance()->deleteAll(true);
        AuthTokenTable::getInstance()->deleteAll(true);
        CertificateBundleTable::getInstance()->deleteAll(true);


        $authToken1 = AuthTokenRecord::createNew(
        '9e0f97cf-22a4-45b5-ad92-7b02d88292a6',
        'token1',
        'token1',
        AuthTokenLevel::NORMAL,
        'A token to grant x service access to certain certificates.'
        );

        $authToken1->save();

        $authToken2 = AuthTokenRecord::createNew(
            '9e0f986a-4545-48cb-a8da-236f89d33c68',
            'token2',
            'token2',
            AuthTokenLevel::NORMAL,
            'A token to grant y service access to certain certificates.'
        );

        $authToken2->save();

        $authToken3 = AuthTokenRecord::createNew(
            '9e0f9b38-39be-42b3-bf1f-dcdc9a93e0ce',
            'fullReadToken',
            'fullReadToken',
            AuthTokenLevel::FULL_READ,
            'A token that has permission to read all certificates'
        );

        $authToken3->save();

        $adminToken = AuthTokenRecord::createNew(
            '9e0fa72c-20b0-4cdb-83a4-670f14b84e28',
            'adminToken',
            'adminToken',
            AuthTokenLevel::ADMIN,
            'A token that has full access.'
        );

        $adminToken->save();

        $authToken2->save();

        $cert1 = CertificateBundleRecord::createNew(
            id: '9e0f98bb-e9b7-461a-9030-ca6aa6eb84a8',
            name: 'cert1',
            fullchain: "myFullChainHere",
            privateKey:  "myPrivateKeyHere",
        );

        $cert1->save();

        $cert2 = CertificateBundleRecord::createNew(
            id: '9e0f9954-2061-4a52-bb0a-63d974bf6366',
            name: 'cert2',
            fullchain: "myFullChainHere",
            privateKey:  "myPrivateKeyHere",
        );

        $cert2->save();

        AuthTokenAssignmentRecord::createNew($authToken1, $cert1)->save();
        AuthTokenAssignmentRecord::createNew($authToken2, $cert2)->save();
    }
}
