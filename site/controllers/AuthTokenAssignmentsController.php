<?php


use Programster\CoreLibs\Exceptions\ExceptionFileAlreadyExists;
use Programster\CoreLibs\Exceptions\ExceptionFileDoesNotExist;
use Programster\CoreLibs\Exceptions\ExceptionMissingExtension;
use Programster\CoreLibs\Filesystem;
use Programster\CoreLibs\StringLib;
use Programster\Http\HttpCode;
use Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException;

class AuthTokenAssignmentsController extends AbstractSlimController
{
    public static function registerRoutes(\Slim\App $app) : void
    {
        $app->get('/api/auth-token-assignments', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokenAssignmentsController($request, $response, $args);
            return $controller->handleRequestToGETAuthTokenAssignments();
        })->addMiddleware(new MiddlewareAdminAuth());;

        $app->post('/api/auth-token-assignments', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokenAssignmentsController($request, $response, $args);
            return $controller->handleRequestToCreateAuthTokenAssignment();
        })->addMiddleware(new MiddlewareAdminAuth());;

        $app->delete('/api/auth-token-assignments/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new AuthTokenAssignmentsController($request, $response, $args);
            return $controller->handleRequestToDeleteAuthTokenAssignment($args['id']);
        })->addMiddleware(new MiddlewareAdminAuth());;
    }


    private function handleRequestToGETAuthTokenAssignments()
    {
        $responseData = [];
        $assignments = AuthTokenAssignmentTable::getInstance()->loadAll();

        foreach ($assignments as $assignment)
        {
            /* @var $assignment AuthTokenAssignmentRecord */
            $responseData[] = [
                'id' => $assignment->getId(),
                'certificate_bundle_id' => $assignment->getCertificateBundleId(),
                'auth_token_id' => $assignment->getAuthTokenId(),
            ];
        }

        return SlimLib::createJsonResponse($responseData, HttpCode::OK);
    }


    private function handleRequestToCreateAuthTokenAssignment()
    {
        try
        {
            $requiredPostFields = [
                'auth_token_id',
                'certificate_bundle_id',
            ];

            $allPostFields = $this->m_request->getParsedBody() ?? [];
            $missingFields = array_diff($requiredPostFields, array_keys($allPostFields));

            if (count($missingFields) > 0)
            {
                $msg = "Missing required fields: " . implode(",", $missingFields);
                throw new ExceptionValidationFailed($msg);
            }

            $authTokenId = $allPostFields['auth_token_id'];
            $certificateId = $allPostFields['certificate_bundle_id'];

            try
            {
                /* @var $authTokenRecord AuthTokenRecord */
                $authTokenRecord = AuthTokenTable::getInstance()->load($authTokenId);
            }
            catch (ExceptionNoSuchIdException)
            {
                throw new ExceptionValidationFailed("That auth token does not exist.");
            }

            try
            {
                /* @var $certificateRecord CertificateBundleRecord */
                $certificateRecord = CertificateBundleTable::getInstance()->load($certificateId);
            }
            catch (ExceptionNoSuchIdException)
            {
                throw new ExceptionValidationFailed("That certificate bundle does not exist.");
            }

            $assignment = AuthTokenAssignmentRecord::createNew($authTokenRecord, $certificateRecord);
            $assignment->save();
            $response = SlimLib::createJsonResponse(['message' => "Auth token assignment created."], HttpCode::CREATED);
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


    private function handleRequestToDeleteAuthTokenAssignment(string $id)
    {
        try
        {
            $authToken = AuthTokenAssignmentTable::getInstance()->load($id);
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
