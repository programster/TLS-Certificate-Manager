<?php


use Programster\Http\HttpCode;
use Psr\Http\Message\ServerRequestInterface;

require_once(__DIR__ . '/../bootstrap.php');



// Manually include/require any classes here that may potentially be stored in the session.
//require_once(__DIR__ . '/../models/MyModel.php');

// start the session here instead of bootstrap, because sessions only apply to web, not scripts.

$app = Slim\Factory\AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addMiddleware(new MiddlewareTrailingSlash()); // this must be last (which means it executes first).

// register the error middleware. This must be registered last so that it gets executed first.
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: (ENVIRONMENT === "dev" || ENVIRONMENT === "staging"),
    logErrors: true,
    logErrorDetails: true
);



// Set the error middlewares 404 handler
$errorMiddleware->setErrorHandler(\Slim\Exception\HttpNotFoundException::class, function (
    ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) {
    $responseBody = ["error" => ["message" => "That route does not exist."]];
    return SlimLib::createJsonResponse($responseBody, HttpCode::NOT_FOUND);
});


$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $payload = ['error' => ['message' => $exception->getMessage()]];

    $response = $app->getResponseFactory()->createResponse();

    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    $response = $response->withStatus(HttpCode::INTERNAL_SERVER_ERROR->value);
    return $response;
};

$errorMiddleware->setDefaultErrorHandler($customErrorHandler);



// Register all of your controllers here. Preferably in alphabetical order.
AuthTokensController::registerRoutes($app);
AuthTokenAssignmentsController::registerRoutes($app);
CertificatesController::registerRoutes($app);


$app->run();
