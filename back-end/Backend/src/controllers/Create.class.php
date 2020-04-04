<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Mailgun\Mailgun;
use \Mailjet\Resources;

class CreateController
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

        /*

        $str = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NzI5NjMwNTYsImV4cCI6MTU3Mjk3MDI1NiwianRpIjoidkdvbDBVYXByTkQ1eGptdUdkZ1A5IiwidXNlciI6eyJ1c2VyX2lkIjoxLCJyb2xlX2lkIjoxfX0.2P1UNL-wistph4sDH3EVJF-XUIgMTuwI5UTsAAu4Y_0';
        $token = base64_decode($str);
        print_r($token['user'[0]]);
        $result = json_decode($token);
        var_dump($result['user']);


        // $_REQUEST[token];
        */



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
            if (
                isset($_REQUEST['firstname']) && $_REQUEST['firstname'] != ''
                && isset($_REQUEST['lastname']) && $_REQUEST['lastname'] != '' 
                && isset($_REQUEST['sex']) && $_REQUEST['sex'] != ''
                && isset($_REQUEST['email']) && $_REQUEST['email'] != ''
                && isset($_REQUEST['roles_id']) && $_REQUEST['roles_id'] != ''
            ) {


                //Est-ce que l'email est valide ?
                if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {

                    if ($this->isEmailExist($_REQUEST['email'])) {
                        $data['status'] = 'error';
                        $data['code'] = 'emailexist';
                        $data['message'] = "Désolé, l'email existe ";
                    } else {

                    // je doit verifier si le mail exist dans la base de donner 


                    // je crée ma requête et la prépare
                    

                        if ($_REQUEST["language_id"] != "null") {
                            $sql = "INSERT INTO users SET firstname = :firstname,
                            lastname = :lastname,
                            sex = :sex,
                            roles_id = :roles_id,
                            email = :email,
                            language_id=:language_id,
                            password = :password;";

                            $stmnt = $db->prepare($sql);

                            $stmnt->bindValue(":language_id", $_REQUEST['language_id'], PDO::PARAM_INT);
                        }
                        else {
                            $sql = "INSERT INTO users SET firstname = :firstname,
                            lastname = :lastname,
                            sex = :sex,
                            roles_id = :roles_id,
                            email = :email,
                            password = :password;";
                            $stmnt = $db->prepare($sql);
                        }


                    $password = $this->password_random(10);


                    //on hash le mot de passe pour le protéger
                    $password_hashed = password_hash($password,  PASSWORD_DEFAULT);

                    // je passe à ma requête les différentes paramètres requis
                    $stmnt->bindValue(":firstname", $_REQUEST['firstname'], PDO::PARAM_STR);
                    $stmnt->bindValue(":lastname", $_REQUEST['lastname'], PDO::PARAM_STR);
                    $stmnt->bindValue(":sex", $_REQUEST['sex'], PDO::PARAM_STR);
                    $stmnt->bindValue(":roles_id", $_REQUEST['roles_id'], PDO::PARAM_STR);
                    $stmnt->bindValue(":email", $_REQUEST['email'], PDO::PARAM_STR);
                    $stmnt->bindValue(":password", $password_hashed, PDO::PARAM_STR);

                    // Exécution de la requête
                    $stmnt->execute();

                    if ($stmnt && $stmnt->rowCount() > 0) {

                        $firstname = $_REQUEST['firstname'];
                        $lastname = $_REQUEST['lastname'];
                        $email = $_REQUEST['email'];
                        $roles = $_REQUEST['roles_id'];

                        // Use your saved credentials, specify that you are using Send API v3.1

                        $mj = new \Mailjet\Client('37ba9cb6bffd1f15aac476a702633bc0', 'e7af3652883f7073c28a999a345d0f8a', true, ['version' => 'v3.1']);
                        // require_once __DIR__ . '/../vendor/autoload.php';

                        // Configure API key authorization: api-key
                        $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', '');

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
                                    'TemplateID' => 1075001,
                                    'TemplateLanguage' => true,
                                    'Subject' => "Inscription Univerbal",
                                    'Variables' => json_decode('{
                                    "firstname": ' . json_encode($firstname) . ',
                                    "lastname": ' . json_encode($lastname) . ',
                                    "mail": ' . json_encode($email) . ',
                                    "password": ' . json_encode($password) . ',
                                    "roles_id": ' . json_encode($roles) . '
                                    }', true)

                                ]
                            ]
                        ];

                        // All resources are located in the Resources class

                        $responses = $mj->post(Resources::$Email, ['body' => $body]);

                        // Read the response

                        $responses->success();


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
                    $data['code'] = 'invalideEmail';
                    $data['message'] = 'Désolé, email invalide';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'paramMissing';
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: email, roles_id, sex, lastname, firstname';
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

    private function isEmailExist($email)
    {
        $db = new MyPDO();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->fetch();
        return $stmt->rowCount() > 0;

    }

  
   
}
