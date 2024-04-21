<?php


use Programster\Http\HttpCode;

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
    \Psr\Http\Message\ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) {
    return SlimLib::createJsonResponse(["error" => ["message" => "That route does not exist."]], HttpCode::NOT_FOUND);
});

// Register all of your controllers here. Preferably in alphabetical order.
CertificatesController::registerRoutes($app);


$app->run();
