<?php


use Programster\CoreLibs\Exceptions\ExceptionFileAlreadyExists;
use Programster\CoreLibs\Exceptions\ExceptionFileDoesNotExist;
use Programster\CoreLibs\Exceptions\ExceptionMissingExtension;
use Programster\CoreLibs\Filesystem;
use Programster\CoreLibs\StringLib;
use Programster\Http\HttpCode;
use Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException;

class CertificatesController extends AbstractSlimController
{
    public static function registerRoutes(\Slim\App $app) : void
    {
        # Get the certificates in the system
        $app->get('/api/certs', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestToListCertificates();
        })->addMiddleware(new MiddlewareAdminAuth());

        # create a new certificate bundle
        $app->post('/api/certs', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->hanldeRequestToCreateNewCertificateBundle();
        })->addMiddleware(new MiddlewareAdminAuth());

        # update certificate bundle
        $app->patch('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->hanldeRequestToUpdateCertificateBundle($args['id']);
        })->addMiddleware(new MiddlewareAdminAuth());

        # get a specific certificate bundle
        $app->get('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestForCertificateBundle($args['id']);
        });

        # delete a certificate bundle
        $app->delete('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestToDeleteCertificateBundle($args['id']);
        })->addMiddleware(new MiddlewareAdminAuth());;
    }


    private function handleRequestToListCertificates()
    {
        $certificates = CertificateBundleTable::getInstance()->loadAll();
        $responseData = [];

        foreach ($certificates as $certificate)
        {
            /* @var $certificate CertificateBundleRecord */
            $authTokens = $certificate->fetchAuthTokens();

            $authTokenSerializedData = [];

            foreach ($authTokens as $authToken)
            {
                /* @var $authToken AuthTokenRecord */
                $authTokenSerializedData[] = $authToken->getPublicArrayForm();
            }

            $responseData[] = [
                'id' => $certificate->getId(),
                'name' => $certificate->getName(),
                'auth_tokens' => $authTokenSerializedData
            ];
        }

        return SlimLib::createJsonResponse($responseData, HttpCode::OK);
    }


    private function handleRequestForCertificateBundle(string $certificateBundleId)
    {
        try
        {
            /* @var $certificateBundle CertificateBundleRecord */
            $certificateBundle = CertificateBundleTable::getInstance()->load($certificateBundleId);

            /* @TODO - authenticate auth token against certificate */
            $this->authenticateCertificateRequest($certificateBundle);

            $responseData = [
                'id' => $certificateBundle->getId(),
                'name' => $certificateBundle->getName(),
                'fullchain' => $certificateBundle->getFullchain(),
                'private_key' => $certificateBundle->getPrivateKey(),
            ];

            $response = SlimLib::createJsonResponse($responseData, HttpCode::OK);
        }
        catch (ExceptionNoSuchIdException)
        {
            $responseData = [
                "error" => [
                    "message" => "That certificate bundle does not exist.",
                ]
            ];

            $response = SlimLib::createJsonResponse($responseData, HttpCode::INTERNAL_SERVER_ERROR);
        }
        catch (ExceptionBadRequest $badRequest)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => $badRequest->getMessage()]],
                HttpCode::BAD_REQUEST
            );
        }
        catch (ExceptionUnauthorized)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => "Authentication failed"]],
                HttpCode::UNAUTHORIZED
            );
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


    /**
     * @param CertificateBundleRecord $certificate
     * @return void
     * @throws ExceptionBadRequest
     * @throws ExceptionUnauthorized - if the user is is not authorized to fetch the specified certificate.
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionQueryError
     * @throws \Programster\PgsqlLib\Exceptions\ExceptionUnexpectedValueType
     */
    private function authenticateCertificateRequest(CertificateBundleRecord $certificate)
    {
        $authHeaders = $this->m_request->getHeader('Authorization');

        if (count($authHeaders) === 0)
        {
            throw new ExceptionBadRequest("Missing required authorization header.");
        }

        $authHeader = $authHeaders[0];

        if (str_contains($authHeader, "Bearer") === false)
        {
            throw new ExceptionBadRequest("Authorization header needs to be a bearer token.");
        }

        $encodedBearerToken = trim(str_replace('Bearer', '', $authHeader));
        $bearerToken = base64_decode($encodedBearerToken);


        if (password_verify($bearerToken, ADMIN_AUTH_TOKEN_HASH))
        {
            $authorized = true;
        }
        else
        {
            // for this specific route, one can use an assigned auth token, check against these.
            $authTokens = $certificate->fetchAuthTokens();
            $authorized = false;

            if (count($authTokens) > 0)
            {
                foreach ($authTokens as $authToken)
                {
                    /* @var $authToken AuthTokenRecord */
                    if (password_verify($bearerToken, $authToken->getTokenHash()))
                    {
                        $authorized = true;
                        break;
                    }
                }
            }
        }

        if (!$authorized)
        {
            throw new ExceptionUnauthorized();
        }
    }


    private function hanldeRequestToUpdateCertificateBundle(string $certificateBundleId)
    {
        try
        {
            $allPostFields = $this->m_request->getParsedBody();

            /* @var $certificateBundleRecord CertificateBundleRecord */
            $certificateBundle = CertificateBundleTable::getInstance()->load($certificateBundleId);

            if (array_key_exists('name', $allPostFields))
            {
                $certificateBundle->setName($allPostFields['name']);
            }

            if (array_key_exists('fullchain', $allPostFields))
            {
                $certificateBundle->setFullchain($allPostFields['fullchain']);
            }

            if (array_key_exists('private_key', $allPostFields))
            {
                $certificateBundle->setPrivateKey($allPostFields['private_key']);
            }

            $certificateBundle->save();
            $response = SlimLib::createJsonResponse(['message' => "Certificate bundle updated."], HttpCode::OK);
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


    private function hanldeRequestToCreateNewCertificateBundle()
    {
        try
        {
            $requiredPostFields = [
                'id',
                'name',
                'fullchain',
                'private_key',
            ];

            $allPostFields = $this->m_request->getParsedBody();
            $missingFields = array_diff($requiredPostFields, array_keys($allPostFields));

            if (count($missingFields) > 0)
            {
                $msg = "Missing required fields: " . implode(",", $missingFields);
                throw new ExceptionValidationFailed($msg);
            }

            /* @todo - verify that id is a UUID, and fullchain/privkey are appropriate for each other */

            if (count(CertificateBundleTable::getInstance()->loadIds([$allPostFields['id']])) > 0)
            {
                throw new ExceptionModelAlreadyExists("A certificate bundle with that ID already exists.");
            }

            if (count(CertificateBundleTable::getInstance()->loadWhereAnd(['name' => $allPostFields['name']])) > 0)
            {
                throw new ExceptionModelAlreadyExists("A certificate bundle with that name already exists.");
            }

            $certificateBundleRecord = CertificateBundleRecord::createNew(
                $allPostFields['id'],
                $allPostFields['name'],
                $allPostFields['fullchain'],
                $allPostFields['private_key'],
            );

            $certificateBundleRecord->save();

            $response = SlimLib::createJsonResponse(['message' => "Certificate bundle created."], HttpCode::CREATED);
        }
        catch (ExceptionValidationFailed|ExceptionModelAlreadyExists $passthruException)
        {
            $responseData = [
                "error" => [
                    "message" => $passthruException->getMessage(),
                ]
            ];

            $response = SlimLib::createJsonResponse($responseData, HttpCode::INTERNAL_SERVER_ERROR);
        }
        catch (Exception $e)
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


    private function handleRequestToDeleteCertificateBundle(string $certificatesBundleId)
    {
        try {
            $certificateBundle = CertificateBundleTable::getInstance()->load($certificatesBundleId);
            $certificateBundle->delete();

            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "Certificate bundle deleted."]],
                HttpCode::OK
            );
        }
        catch (ExceptionModelNotFound $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "A certificate bundle with that ID does not exist."]],
                HttpCode::NOT_FOUND
            );
        }

        return $newResponse;
    }
}
