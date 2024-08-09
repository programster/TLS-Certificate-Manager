<?php


use Programster\Http\HttpCode;
use Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException;

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
                'id',
                'name',
                'base64_encoded_token',
                'description'
            ];

            $allPostFields = $this->m_request->getParsedBody();
            $missingFields = array_diff($requiredPostFields, array_keys($allPostFields));

            if (count($missingFields) > 0)
            {
                $msg = "Missing required fields: " . implode(",", $missingFields);
                throw new ExceptionValidationFailed($msg);
            }

            /* @todo - verify that id is a UUID, and fullchain/privkey are appropriate for each other */

            $decodedToken = base64_decode($allPostFields['base64_encoded_token']);

            if (count(AuthTokenTable::getInstance()->loadIds([$allPostFields['id']])) > 0 )
            {
                throw new ExceptionValidationFailed("An auth token with that ID already exists.");
            }

            if (count(AuthTokenTable::getInstance()->loadWhereAnd(['name' => $allPostFields['name']])) > 0)
            {
                throw new ExceptionValidationFailed("An auth token with that name already exists.");
            }

            $authTokenRecord = AuthTokenRecord::createNew(
                $allPostFields['id'],
                $allPostFields['name'],
                $decodedToken,
                $allPostFields['description'],
            );

            $authTokenRecord->save();

            $response = SlimLib::createJsonResponse(['message' => "Auth token saved."], HttpCode::CREATED);
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
        catch (Exception)
        {
            $responseData = [
                "error" => [
                    "message" => "Whoops! Something went wrong. Please try again or contact support.",
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
