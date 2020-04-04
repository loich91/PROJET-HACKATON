<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class PasswordrecupController
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
        //session_start();
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
        $data['code_recup'] = 'int';
        $httpCode = 200;


        //var_dump($_REQUEST['email']);
        //on s'attend à recevoir email_submit et email et on vérifie si l'adresse mail est dans notre table users
        if (isset($_REQUEST['email'])) {


            if (!empty($_REQUEST['email'])) {


                $recup_mail = htmlspecialchars($_REQUEST['email']);
                //filter_var pour vérifier si l'émail est bien au format email
                if (filter_var($recup_mail, FILTER_VALIDATE_EMAIL)) {
                    $mailexist = $db->prepare('SELECT id_users, firstname 
                                                FROM users 
                                                WHERE email = ?');
                    //vérification du mail dans la table
                    $mailexist->execute(array($recup_mail));
                    $mailexist_count = $mailexist->rowCount();
                    if ($mailexist_count == 1) {

                        // on récupère le firstname si le mail existe pour un user
                        $firstname = $mailexist->fetch();

                        $firstname = $firstname['firstname'];


                        //on book le mail dans la variable


                        // on crée ainsi notre code avec la boucle for 
                        $recup_code = "";
                        for ($i = 0; $i < 8; $i++) {
                            $recup_code .= mt_rand(0, 9);
                        }



                        $mail_recup_exist = $db->prepare('SELECT id_recup FROM password_recovery WHERE email = ?');
                        // $mail_recup_exist = $db->prepare('SELECT id_recup FROM password_recovery WHERE email = :email');

                        //ajout d'un vbind
                        $mail_recup_exist->execute(array($recup_mail));
                        // $mail_recup_exist->execute(array(':email' => $recup_mail));
                        $mail_recup_exist = $mail_recup_exist->rowCount();
                        if ($mail_recup_exist == 1) {
                            $recup_insert = $db->prepare('UPDATE password_recovery SET code = ? WHERE email = ?');
                            $recup_insert->execute(array($recup_code, $recup_mail));
                        } else {
                            $recup_insert = $db->prepare('INSERT INTO password_recovery (email,code) VALUES (?, ?)');
                            $recup_insert->execute(array($recup_mail, $recup_code));
                        }
                        //on envoie un mail à l'utilsateur avec le nom et un lien qui renvoie à la page reconnexion ( saisir
                        // le nouveau mot de passe)
                        $headers[] = 'MIME-Version: 1.0';
                        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                        $headers[] = 'From: Your firstname <sirius@info.com>';
                        $message = '
                        <html>
                        <head>
                        <title>Récupération de mot de passe </title>
                        <meta charset="utf-8" />
                        </head>
                            <body>
                            <font color="#303030";>
                                <div align="center">
                                <table width="600px">
                                    <tr>
                                    <td>
                                        
                                        <div align="center">Bonjour <b>' . $firstname .  '</b></div>
                                        Voici votre code de récupération: <b>' . $recup_code . '</b>
                                        <a href="http://localhost/softkidoe/changpassword?code=$recup_code.&email=$recup_email">le lien password   </a>
                                        A bientôt sur <a href="#">By Sirius</a> !
                                        
                                    </td>
                                    </tr>
                                    <tr>
                                    <td align="center">
                                        <font size="2">
                                        Ceci est un email automatique, merci de ne pas y répondre
                                        </font>
                                    </td>
                                    </tr>
                                </table>
                                </div>
                            </font>
                            </body>
                        </html>
                        ';

                        mail($recup_mail, "Récupération de mot de passe", $message,  implode("\r\n", $headers));
                        $httpCode = 200;
                        $data['status'] = 'success';
                        $data['code_recup'] =  $recup_code;
                        $data['message'] =  "NewParam: email code new password.";
                    } else {
                        $data['status'] = 'error';
                        $data['message'] = "Cette adresse mail n'est pas enregistrée";
                    }
                } else {
                    $data['status'] = 'error';
                    $data['message'] = "Adresse mail invalide";
                }
            } else {
                $data['status'] = 'error';
                $data['message'] = "Veuillez entrer votre adresse mail";
            }
        } else {
            $data['status'] = 'error';
            $data['message'] = "Param email";
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