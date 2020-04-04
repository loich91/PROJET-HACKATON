<?php
// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $response = array();
    $response['status'] = 'error';
    $response['code '] = $exception->getCode();
    $payload = ['status' => 'error', 'code' => $exception->getCode(), 'message' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($exception->getCode());
};

?>