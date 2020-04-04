<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class NewrecupController
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

        $db = new MyPDO();


        // récupération des paramètres envoyer qu'on met dans le tableau $userArray
        $uri = $request->getUri();
        $userArray = null;
        parse_str($uri->getQuery(), $userArray);

        // le status par défaut est une erreur
        // je vais utiliser les numéros http pour indiquer
        // si ça c'est bien ou pas bien passé...
        $data = array();
        $data['status'] = 'anyone';
        $data['message'] = 'NoParam';

        $httpCode = 200;

        //on s'attend à recevoir email_submit et email et on vérifie si l'adresse mail est dans notre table users


        if (isset($_REQUEST['email'],
        $_REQUEST['verif_code'],
        $_REQUEST['changed_password'])) {

            if (
                !empty($_REQUEST['email'])
                && !empty($_REQUEST['verif_code'])
                && !empty($_REQUEST['changed_password'])
            ) {
                $email = htmlspecialchars($_REQUEST['email']);
                $verif_code = htmlspecialchars($_REQUEST['verif_code']);
                $password = htmlspecialchars($_REQUEST['changed_password']);

                $verif_req = $db->prepare('SELECT id_recup FROM password_recovery WHERE email = ? AND code = ?');
                $verif_req->execute(array($email, $verif_code));
                $verif_req = $verif_req->rowCount();
                if ($verif_req == 1) {
                    $password = sha1($password);
                    $ins_password = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
                    $ins_password->execute(array($password, $email));
                    $del_req = $db->prepare('UPDATE `password_recovery` SET `code` = NULL WHERE `password_recovery`.`email` = ?;');
                    $del_req->execute(array($email));


                    $data['status'] = 'success';
                    $data['message'] = "nouveau mot de password ok.";
                    $httpCode = 200;
                } else {
                    $data['status'] = 'error';
                    $data['message'] = "Code invalide";
                }
            } else {
                $data['status'] = 'error';
                $data['message'] = "NoParam email, code, password";
            }
        }else{
            $data['message']="Param email verif_code changed_password";
        }

        // je ferme la connexion à ma base de donnée
        unset($db);

        // je converti mon tableau data en JSON et le retourne via SLIM
        $payload = json_encode($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->withStatus($httpCode);
    }
}