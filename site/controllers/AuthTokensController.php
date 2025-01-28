<?php


use Cassandra\Exception\ValidationException;
use Programster\CoreLibs\StringLib;
use Programster\Http\HttpCode;
use Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException;
use Programster\PgsqlObjects\Utils;

class AuthTokensController extends AbstractSlimController
{
    public static function registerRoutes(\Slim\App $app) : void
    {
        $app->get('/api/auth-tokens', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokensController($request, $response, $args);
            return $controller->handleRequestToFetchAuthTokens();
        })->addMiddleware(new MiddlewareAdminAuth());

        $app->post('/api/auth-tokens', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokensController($request, $response, $args);
            return $controller->hanldeRequestToCreateNewAuthToken();
        })->addMiddleware(new MiddlewareAdminAuth());

        $app->delete('/api/auth-tokens/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokensController($request, $response, $args);
            return $controller->handleRequestToDeleteAuthToken($args['id']);
        })->addMiddleware(new MiddlewareAdminAuth());
    }


    private function handleRequestToFetchAuthTokens()
    {
        $authTokens = AuthTokenTable::getInstance()->loadAll();
        $responseData = [];

        foreach ($authTokens as $authToken)
        {
            /* @var $authToken AuthTokenRecord */
            $certificates = $authToken->fetchCertificates();
            $serializedCertificates = [];

            foreach ($certificates as $certificate)
            {
                /* @var $certificate CertificateBundleRecord */
                $serializedCertificates[] = [
                    'id' => $certificate->getId(),
                    'name' => $certificate->getName(),
                ];
            }

            $responseData[] = array_merge($authToken->getPublicArrayForm(), ['certificates' => $serializedCertificates]);
        }

        return SlimLib::createJsonResponse($responseData, HttpCode::OK);
    }

    private function hanldeRequestToCreateNewAuthToken()
    {
        try
        {
            $requiredPostFields = [
                'name',
                'level',
                'description'
            ];

            $id = Utils::generateUuid();
            $token = StringLib::generateRandomString(32, useSpecialChars: false);

            $allPostFields = $this->m_request->getParsedBody();

            if ($allPostFields === null || count($allPostFields) === 0)
            {
                $missingFields = $requiredPostFields;
            }
            else
            {
                $missingFields = array_diff($requiredPostFields, array_keys($allPostFields));
            }

            if (count($missingFields) > 0)
            {
                $msg = "Missing required fields: " . implode(",", $missingFields);
                throw new ExceptionValidationFailed($msg);
            }

            /* @todo - verify that id is a UUID, and fullchain/privkey are appropriate for each other */

            if (count(AuthTokenTable::getInstance()->loadWhereAnd(['name' => $allPostFields['name']])) > 0)
            {
                throw new ExceptionValidationFailed("An auth token with that name already exists.");
            }

            $level = $allPostFields['level'];
            $description = $allPostFields['description'];
            $name = $allPostFields['name'];

            try
            {
                $authTokenLevel = AuthTokenLevel::from($level);
            }
            catch (ValueError)
            {
                throw new ExceptionBadRequest("{$level} is not a valid auth token level.");
            }

            $authTokenRecord = AuthTokenRecord::createNew(
                $id,
                $allPostFields['name'],
                $token,
                $authTokenLevel,
                $description,
            );

            $authTokenRecord->save();

            $responseData = [
                'id' => $id,
                'token' => base64_encode($token),
                'name' => $name,
                'level' => $authTokenLevel,
                'description' => $description,
            ];

            $response = SlimLib::createJsonResponse($responseData, HttpCode::CREATED);
        }
        catch (ExceptionValidationFailed $validationFailedError)
        {
            $responseData = [
                "error" => [
                    "message" => $validationFailedError->getMessage(),
                ]
            ];

            $response = SlimLib::createJsonResponse($responseData, HttpCode::INTERNAL_SERVER_ERROR);
        }

        return $response;
    }


    private function handleRequestToDeleteAuthToken(string $id)
    {
        try
        {
            $authToken = AuthTokenTable::getInstance()->load($id);
            $authToken->delete();

            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "Auth token deleted."]],
                HttpCode::OK
            );
        }
        catch (ExceptionNoSuchIdException $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "An auth token with that ID does not exist."]],
                HttpCode::NOT_FOUND
            );
        }

        return $newResponse;
    }
}
