<?php

/**
 * Middleware that ensures that an auth token was provided, and that it is a token within our system.
 */

use Programster\Http\HttpCode;
use Programster\PgsqlObjects\Exceptions\ExceptionNoSuchIdException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareTokenAuth implements \Psr\Http\Server\MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try
        {
            try
            {
                $authToken = Auth::getAuthToken($request);
            }
            catch (ExceptionNoSuchIdException | ExceptionBadRequest)
            {
                throw new ExceptionUnauthorized();
            }

            $response = $handler->handle($request);
        }
        catch (ExceptionUnauthorized | ExceptionVerificationFailed)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => "Authentication failed"]],
                HttpCode::UNAUTHORIZED
            );
        }
        catch (ExceptionPermissionDenied $e)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => $e->getMessage()]],
                HttpCode::FORBIDDEN
            );
        }

        return $response;
    }
}
