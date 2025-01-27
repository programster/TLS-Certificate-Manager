<?php

use Psr\Http\Message\ServerRequestInterface;

class Auth
{
    /**
     * @param ServerRequestInterface $request
     * @return AuthTokenRecord - the auth token, (if all checks passed)
     * @throws ExceptionBadRequest - if failed to provide auth headers.
     * @throws ExceptionVerificationFailed - if the token with that ID exists, but incorrect token/secret provided
     * @throws \Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException - if there is no ID with the token.
     */
    public static function getAuthToken(ServerRequestInterface $request) : AuthTokenRecord
    {
        if (   array_key_exists('PHP_AUTH_USER', $_SERVER) === false
            || array_key_exists('PHP_AUTH_PW', $_SERVER) === false)
        {
            throw new ExceptionBadRequest("Missing required HTTP basic auth header.");
        }

        $requestEncodedToken = $_SERVER['PHP_AUTH_PW'];
        $tokenId = $_SERVER['PHP_AUTH_USER'];
        $requestSecret = base64_decode($requestEncodedToken);

        // first check if is from the env file
        if ($tokenId === ADMIN_AUTH_TOKEN_ID)
        {
            if (password_verify($requestSecret, ADMIN_AUTH_TOKEN_HASH) === false)
            {
                die("{$requestSecret} did not match for hash " . ADMIN_AUTH_TOKEN_HASH . PHP_EOL);
                throw new ExceptionVerificationFailed();
            }

            $authToken = AuthTokenRecord::createNew(
                $tokenId,
                'Primary Admin Auth Token',
                $requestSecret,
                AuthTokenLevel::ADMIN,
                'Auth token set in the environment variables.'
            );
        }
        else
        {

            /* @var $authToken AuthTokenRecord */
            try
            {
                $authToken = AuthTokenTable::getInstance()->load($tokenId);
            }
            catch (Exception)
            {
                throw new ExceptionVerificationFailed();
            }

            $authToken->verify($requestEncodedToken);
        }

        return $authToken;
    }
}
