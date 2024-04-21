<?php


use Programster\CoreLibs\Exceptions\ExceptionFileAlreadyExists;
use Programster\CoreLibs\Exceptions\ExceptionFileDoesNotExist;
use Programster\CoreLibs\Exceptions\ExceptionMissingExtension;
use Programster\CoreLibs\Filesystem;
use Programster\CoreLibs\StringLib;
use Programster\Http\HttpCode;

class CertificatesController extends AbstractSlimController
{
    public static function registerRoutes(\Slim\App $app) : void
    {
        $app->get('/', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return SlimLib::createJsonResponse(['routes' => ['GET /certs/{uuid}']], HttpCode::OK);
        });

        $app->get('/certs/{id:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', function (Slim\Psr7\Request $request, Slim\Psr7\Response $response, $args) {
            $controller = new CertificatesController($request, $response, $args);
            return $controller->handleRequestForCertificates($args['id']);
        });
    }

    private function handleRequestForCertificates(string $certificatesBundleId)
    {
        try
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

            $configLoc = "/configs/certs.yaml";
            if (file_exists($configLoc) === false)
            {
                throw new ExceptionFileDoesNotExist("Certificates configuration file does not exist yet.");
            }

            $certsConfig = yaml_parse_file("/configs/certs.yaml");

            if ($certsConfig === false)
            {
                throw new ExceptionFileDoesNotExist("Certificates configuration is not a valid YAML file.");
            }

            if (array_key_exists($certificatesBundleId, $certsConfig) === false)
            {
                die(print_r($certsConfig, true));
                throw new ExceptionModelNotFound("A certificate bundle with that ID does not exist.");
            }



            $encodedBearerToken = trim(str_replace('Bearer', '', $authHeader));
            $bearerToken = base64_decode($encodedBearerToken);

            $validTokenHashes = $certsConfig[$certificatesBundleId]['authTokens'];
            $authenticated = false;

            foreach ($validTokenHashes as $validTokenHash)
            {
                if (password_verify($bearerToken, $validTokenHash))
                {
                    $authenticated = true;
                    break;
                }
            }

            if (!$authenticated)
            {
                throw new ExceptionUnauthorized();
            }

            $path = $certsConfig[$certificatesBundleId]['path'];
            $expectedFolder = "/certs/{$path}";

            if (file_exists($expectedFolder) === false)
            {
                throw new ExceptionFileDoesNotExist("Certificate files are missing from the server.");
            }

            $parentTmpDir = sys_get_temp_dir() . "/certs-" . StringLib::generateRandomString(12, false);
            $tmpDir = "{$parentTmpDir}/certs";
            Filesystem::mkdir($tmpDir);
            shell_exec("cp -r {$expectedFolder}/* {$tmpDir}/.");
            $zipFilepath = tempnam(sys_get_temp_dir(), "certs-") . ".zip";
            Filesystem::zipDir($tmpDir, $zipFilepath, false);
            Filesystem::streamFileToBrowser($zipFilepath, 'certs.zip', 'application/x-zip');

            Filesystem::deleteDir($parentTmpDir);
            unlink($zipFilepath);
            die();
        }
        catch (ExceptionFileDoesNotExist $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => $e->getMessage()]],
                HttpCode::INTERNAL_SERVER_ERROR
            );
        }
        catch (ExceptionModelNotFound $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => $e->getMessage()]],
                HttpCode::NOT_FOUND
            );
        }
        catch (ExceptionUnauthorized $e)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "Authentication failed. Please check you have a valid token for this resource."]],
                HttpCode::UNAUTHORIZED
            );
        }
        catch (ExceptionBadRequest $badRequestException)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => $badRequestException->getMessage()]],
                HttpCode::UNAUTHORIZED
            );
        }
        catch (ExceptionMissingExtension)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "The server is missing the ZIP extension."]],
                HttpCode::INTERNAL_SERVER_ERROR
            );
        }
        catch (ExceptionFileAlreadyExists $p)
        {
            $newResponse = SlimLib::createJsonResponse(
                ['error' => ['message' => "Whoops! Something went wrong. Please try again. {$p->getMessage()}"]],
                HttpCode::INTERNAL_SERVER_ERROR
            );
        }

        return $newResponse;
    }
}
