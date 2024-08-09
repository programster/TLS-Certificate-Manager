<?php

use Programster\Http\HttpCode;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareAdminAuth implements \Psr\Http\Server\MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try
        {
            $authHeaders = $request->getHeader('Authorization');

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

            if (password_verify($bearerToken, ADMIN_AUTH_TOKEN_HASH) === FALSE)
            {
                throw new ExceptionUnauthorized();
            }

            $response = $handler->handle($request);
        }
        catch (ExceptionBadRequest $e)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => $e->getMessage()]],
                HttpCode::UNAUTHORIZED
            );
        }
        catch (ExceptionUnauthorized)
        {
            $response = SlimLib::createJsonResponse(
                ["error" => ["message" => "Authentication failed"]],
                HttpCode::UNAUTHORIZED
            );
        }

        return $response;
    }
}
