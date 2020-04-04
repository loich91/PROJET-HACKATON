<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use \Mailjet\Resources;

class PasswordController
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
        // connexion à la BDD
        $db = new MyPDO();


        // récupération des paramètres envoyer qu'on met dans le tableau $userArray
        $uri = $request->getUri();
        $userArray = null;
        parse_str($uri->getQuery(), $userArray);

        // le status par défaut est une erreur
        // je vais utiliser les numéros http pour indiquer
        // si ça c'est bien ou pas bien passé...
        $data = array();
        $data['status'] = 'error';
        $httpCode = 200;


        // j'écrit des conditions pour obtenir une des routes REST disponible
        // 1. GET sans ID = liste
        // 2. GET avec ID = détail de l'entrée X
        // 3. POST sans ID = Ajout d'une nouvelle entrée
        // 4. PUT/PATCH avec ID = Modification de l'entée X
        // 5. DELETE avec ID = Suppression de l'entrée X
        // 6. Aucune route ne correspond, retour erreur REST


        // 3. POST sans ID = Ajout d'une nouvelle entrée
        if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente
            if (isset($_REQUEST['email']) && $_REQUEST['email'] != '') {

                //Est-ce que l'email est valide ?
                if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {


                    $sql1 = "SELECT * FROM users WHERE email = :email";


                    $stmnt1 = $db->prepare($sql1);
                    $stmnt1->bindValue(":email", $_REQUEST['email'], PDO::PARAM_STR);
                    $stmnt1->execute();


                    if ($stmnt1 && $stmnt1->rowCount() > 0) {
                        $result = $stmnt1->fetch(PDO::FETCH_ASSOC);


                        // je crée ma requête et la prépare
                        $sql2 = "UPDATE `users` SET 
                        password = :password
                        WHERE id_users = :id_users;";

                        $stmnt = $db->prepare($sql2);

                        $password = $this->password_random(10);


                        //on hash le mot de passe pour le protéger
                        $password_hashed = password_hash($password,  PASSWORD_DEFAULT);

                        // je passe à ma requête les différentes paramètres requis
                        $stmnt->bindValue(":id_users", $result['id_users'], PDO::PARAM_INT);
                        $stmnt->bindValue(":password", $password_hashed, PDO::PARAM_STR);

                        // Exécution de la requête
                        $stmnt->execute();


                        $firstname = $result['firstname'];
                        $lastname = $result['lastname'];
                        $email = $result['email'];


                        // Use your saved credentials, specify that you are using Send API v3.1

                        $mj = new \Mailjet\Client('37ba9cb6bffd1f15aac476a702633bc0', 'e7af3652883f7073c28a999a345d0f8a', true, ['version' => 'v3.1']);
                        // require_once __DIR__ . '/../vendor/autoload.php';


                        // Define your request body

                        $body = [
                            'Messages' => [
                                [
                                    'From' => [
                                        'Email' => "mohkeita@proximus.be",
                                        'Name' => "Univerbal"
                                    ],
                                    'To' => [
                                        [
                                            'Email' => "$email",
                                            'Name' => "$firstname $lastname"
                                        ]
                                    ],
                                    'TemplateID' => 1082083,
                                    'TemplateLanguage' => true,
                                    'Subject' => "Votre nouveau mot de passe",
                                    'Variables' => json_decode('{
                                    "firstname": ' . json_encode($firstname) . ',
                                    "lastname": ' . json_encode($lastname) . ',
                                    "password": ' . json_encode($password) . '
                                    }', true)

                                ]
                            ]
                        ];

                        // All resources are located in the Resources class

                        $responses = $mj->post(Resources::$Email, ['body' => $body]);

                        // Read the response

                        $responses->success() && var_dump($responses->getData());


                        $httpCode = 200;
                        $data['status'] = 'success';
                    } else {
                        $data['status'] = 'error';
                        $data['code'] = 'sqlProblem';
                        $data['content'] = 'Désolé impossible d\'exécuter la requête...';
                    }
                } else {
                    $data['status'] = 'error';
                    $data['code'] = 'invalideEmail';
                    $data['message'] = 'Désolé, email invalide';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'paramMissing';
                $data['message'] = "Désolé, l'email est obligatoire";
            }
        } else if (($request->getMethod() == 'PATCH' || $request->getMethod() == 'PUT') && !isset($args['id'])) {
            // je vérifie que l'id est bien numérique

            parse_str(file_get_contents('php://input'), $_PUT);




            // je vérifie que toutes les entrées requises sont bien présentes
            if (
                isset($_PUT['ancien']) && $_PUT['ancien'] != ''
                && isset($_PUT['nouveau']) && $_PUT['nouveau'] != ''
                && isset($_PUT['id_users']) && $_PUT['id_users'] != ''
            ) {


                // $password_hash = password_hash($_REQUEST['ancien'],  PASSWORD_DEFAULT);

                $sql1 = "SELECT * FROM users WHERE id_users = :id_users";


                $stmnt1 = $db->prepare($sql1);
                $stmnt1->bindValue(":id_users", $_PUT['id_users'], PDO::PARAM_INT);
                // $stmnt1->bindValue(":password", $password_hash1, PDO::PARAM_STR);
                $stmnt1->execute();





                if ($stmnt1 && $stmnt1->rowCount() > 0) {

                    $result = $stmnt1->fetch(PDO::FETCH_ASSOC);


                    $hash = $result['password'];

                    if (password_verify($_PUT['ancien'], $hash)) {

                        if (!$this->passwordCheck($_PUT['nouveau'])) {
                            $data['status'] = 'error';
                            $data['code'] = 'passFormat';
                            $data['message'] = 'Désolé, mdp invalide: uppercase+lowercase > 8';
                        } else {

                            // j'écrit ma requête et la prépare
                            $sql = "UPDATE `users` SET 
                            password = :password
                            WHERE id_users = :user_id;";

                            $stmnt = $db->prepare($sql);

                            //on hash le mot de passe pour le protéger
                            $password_hashed = password_hash($_PUT['nouveau'],  PASSWORD_DEFAULT);

                            // je passe les différentes paramètres à ma requête.
                            $stmnt->bindValue(":password", $password_hashed, PDO::PARAM_STR);
                            $stmnt->bindValue(":user_id", $result['id_users'], PDO::PARAM_INT);


                            // Exécution de la requête
                            $stmnt->execute();

                            if ($stmnt && $stmnt->rowCount() > 0) {


                                $firstname = $result['firstname'];
                                $lastname = $result['lastname'];
                                $email = $result['email'];


                                //email de remerciement pour changement de mot de passe


                                // Use your saved credentials, specify that you are using Send API v3.1

                                $mj = new \Mailjet\Client('37ba9cb6bffd1f15aac476a702633bc0', 'e7af3652883f7073c28a999a345d0f8a', true, ['version' => 'v3.1']);
                                // require_once __DIR__ . '/../vendor/autoload.php';


                                // Define your request body

                                $body = [
                                    'Messages' => [
                                        [
                                            'From' => [
                                                'Email' => "mohkeita@proximus.be",
                                                'Name' => "Univerbal"
                                            ],
                                            'To' => [
                                                [
                                                    'Email' => "$email",
                                                    'Name' => "$firstname $lastname"
                                                ]
                                            ],
                                            'TemplateID' => 1082304,
                                            'TemplateLanguage' => true,
                                            'Subject' => "Le mot de passe a bien été modifié",
                                            'Variables' => json_decode('{
                                            "firstname": ' . json_encode($firstname) . ',
                                            "lastname": ' . json_encode($lastname) . '
                                            }', true)

                                        ]
                                    ]
                                ];

                                // All resources are located in the Resources class

                                $responses = $mj->post(Resources::$Email, ['body' => $body]);

                                // Read the response

                                $responses->success() && var_dump($responses->getData());

                                // code envoi mail


                                $httpCode = 200;
                                $data['status'] = 'success';
                            } else {
                                $data['status'] = 'error';
                                $data['code'] = 'sqlProblem';
                                $data['content'] = 'Désolé impossible d\'exécuter la requête...';
                            }
                        }
                    } else {
                        $data['status'] = 'error';
                        $data['code'] = 'passwordprobleme';
                        $data['message'] = "Désolé, votre ancien mot de passe est invalide";
                    }
                } else {
                    $data['status'] = 'error';
                    $data['code'] = 'id_usernotfound';
                    $data['message'] = "Désolé, id_users et le mot de password ne correspond pas";
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'paramMissing';
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: Anciens, Nouvelle mot de passe et id user';
            }
        } else {
            // aucune des routes REST n'a été rencontrée
            // j'informe mon utilisateur qu'il doit respecter la norme
            // REST !

            $data['status'] = 'error';
            $data['code'] = 'badParam';
            $data['message'] = 'Veuillez utiliser la norme REST.';
        }

        // je ferme la connexion à ma base de donnée
        unset($db);

        // je converti mon tableau data en JSON et le retourne via SLIM
        $payload = json_encode($data);



        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', '*')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->withStatus($httpCode);
    }

    /*
        Fonction qui vérifie que le mot de passe
        aie des minuscules, des majuscules, des nombres
        et aie + de 10 caracteres
    */
    private function password_random($length)
    {
        $alphabet = "0123456789azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN";
        return substr(str_shuffle(str_repeat($alphabet, $length)), 0, $length);
    }

    private function passwordCheck($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);

        if (!$uppercase || !$lowercase || strlen($password) < 8) {
            return false;
        } else {
            return true;
        }
    }
}
