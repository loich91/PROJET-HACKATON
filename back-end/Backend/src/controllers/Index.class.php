<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class IndexController
{
    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        // c'est toujours plus "propre" de répondre à l'utilisateur 
        // pour lui indiquer ce qui ne vas pas...

        // tableau avec les données que je vais retourner à l'utilisateur
        $data = array(
            'status' => 'error',
            'code' => '',
            'message' => 'Merci d\'utiliser les routes mise à votre disposition: ... ',
        );

        // conversion du tableau en json et on réponds avec slim...
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
