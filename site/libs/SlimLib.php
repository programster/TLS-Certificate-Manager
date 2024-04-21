<?php

/*
 * A library of functions to help with using Slim.
 */

use Programster\Http\HttpCode;

class SlimLib
{
    public static function createJsonResponse(array $responseData, HttpCode $httpStatusCode) : \Slim\Psr7\Response
    {

        $responseBody = json_encode($responseData);
        $response = new \Slim\Psr7\Response($httpStatusCode->value);
        $response->getBody()->write($responseBody);
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    }
}
