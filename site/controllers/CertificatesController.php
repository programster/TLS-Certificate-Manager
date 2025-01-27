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
        })->addMiddleware(new MiddlewareTokenAuth());

        # create a new certificate bundle
        $app->post('/api/certs', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestToCreateNewCertificateBundle();
        })->addMiddleware(new MiddlewareTokenAuth());

        # update certificate bundle
        $app->patch('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->hanldeRequestToUpdateCertificateBundle($args['id']);
        })->addMiddleware(new MiddlewareTokenAuth());

        # get a specific certificate bundle
        $app->get('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestForCertificateBundle($args['id']);
        })->addMiddleware(new MiddlewareTokenAuth());

        # delete a certificate bundle
        $app->delete('/api/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestToDeleteCertificateBundle($args['id']);
        })->addMiddleware(new MiddlewareAdminAuth());;
    }


    private function handleRequestToListCertificates()
    {
        $authToken = Auth::getAuthToken();
        $certificates = CertificateBundleTable::getInstance()->fetchForAuthTokenReadAccess($authToken);
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
            $authToken = Auth::getAuthToken($this->m_request);

            if (!$authToken->hasReadCertificatePermission($certificateBundle))
            {
                throw new ExceptionPermissionDenied("You do not have permission to read this certificate.");
            }

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

        return $response;
    }


    private function hanldeRequestToUpdateCertificateBundle(string $certificateBundleId)
    {
        try
        {
            /* @var $certificateBundle CertificateBundleRecord */
            $certificateBundle = CertificateBundleTable::getInstance()->load($certificateBundleId);
            $authToken = Auth::getAuthToken($this->m_request);

            if (!$authToken->hasUpdateCertificatePermission($certificateBundle))
            {
                throw new ExceptionPermissionDenied("You do not have permission to update this certificate.");
            }

            $allPostFields = $this->m_request->getParsedBody();

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


    private function handleRequestToCreateNewCertificateBundle()
    {
        try
        {
            $authToken = Auth::getAuthToken($this->m_request);

            if (in_array($authToken->getAccessLevel(), [AuthTokenLevel::ADMIN, AuthTokenLevel::CERTIFICATE_CREATOR]) === false)
            {
                throw new ExceptionPermissionDenied("You need to have an admin or creator token to create certificates.");
            }

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

            AuthTokenAssignmentRecord::createNew($authToken, $certificateBundleRecord)->save();
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
        try
        {
            $authToken = Auth::getAuthToken();
            /* @var $certificateBundle CertificateBundleRecord */
            $certificateBundle = CertificateBundleTable::getInstance()->load($certificatesBundleId);

            if ($authToken->hasDeleteCertificatePermission($certificateBundle) === false)
            {
                throw new ExceptionPermissionDenied("You do not have permission to delete that certificate.");
            }

            $certificateBundle->delete();

            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "Certificate bundle deleted."]],
                HttpCode::OK
            );
        }
        catch (ExceptionNoSuchIdException $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "A certificate bundle with that ID does not exist."]],
                HttpCode::NOT_FOUND
            );
        }

        return $newResponse;
    }
}
