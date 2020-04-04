<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class UsersController
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

        // 1. GET sans ID = liste        
        if ($request->getMethod() == 'GET' && !isset($args['id'])) {
            // j'écrit ma requete SQL et je la prépare
            $sql = "SELECT *
            FROM users;";


            $stmnt = $db->prepare($sql);
            $stmnt->execute();

            // je vérifie que tout est ok, et retourne la réponse
            if ($stmnt && $stmnt->rowCount() > 0) {
                $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

                $httpCode = 200;
                $data['status'] = 'success';
                $data['content'] = $result;
            } else {
                $data['status'] = 'error';
                $data['code'] = '';
            }
        }
        // 2. GET avec ID = détail de l'entrée X
        else if ($request->getMethod() == 'GET' && isset($args['id'])) {
            // je vérifie que l'ID est bien numérique
            //var_dump($_REQUEST);
            if (is_numeric($args['id'])) {
                // je prépare ma requête et l'exécute
                $sql = "SELECT firstname, lastname, sex, email, roles_id, L.name AS language
                        FROM users 
                        LEFT JOIN language AS L ON id_language=language_id
                        WHERE id_users = :user_id;";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":user_id", $args['id'], PDO::PARAM_INT);

                // Exécution de la requête
                $stmnt->execute();

                // je vérifie que tout est ok, et retourne la réponse
                // je vérifie aussi qu'il y a bien un résultat, sinon 
                // je retourne une erreur
                if ($stmnt && $stmnt->rowCount() > 0) {
                    $result = $stmnt->fetch(PDO::FETCH_ASSOC);

                    if ($result) {
                        $httpCode = 200;
                        $data['status'] = 'success';
                        $data['content'] = $result;
                    } else {
                        $data['status'] = 'error';
                        $data['code'] = 'noEntry';
                        $data['content'] = 'L\'entée ' . $args['id'] . ' ne retourne aucun résultat... ';
                    }
                } else {
                    $data['status'] = 'error';
                    $data['code'] = 'sqlProblem';
                    $data['content'] = 'Désolé impossible d\'exécuter la requête...';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'idMustBeNumeric';
                $data['content'] = 'Désolé l\'id doit être numérique.';
            }
        }

/*

        // 3. POST sans ID = Ajout d'une nouvelle entrée
        if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente
            var_dump($_REQUEST);
            if(
                isset($_REQUEST['firstname']) && $_REQUEST['firstname'] != ''
            && isset($_REQUEST['lastname']) && $_REQUEST['lastname'] != '' &&
            isset($_REQUEST['sex']) && $_REQUEST['sex'] != ''
            && isset($_REQUEST['email']) && $_REQUEST['email'] != ''
            && isset($_REQUEST['roles_id']) && $_REQUEST['roles_id'] != ''
            ){
                var_dump($_REQUEST);
                 //Est-ce que l'email est valide ?
            if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {

                if ($this->isEmailExist($_REQUEST['email'])) {
                    $data['status'] = 'error';
                    $data['code'] = 'emailexist';
                    $data['message'] = "Désolé, l'email existe ";
                } else {

                     // je crée ma requête et la prépare
                $sql = "INSERT INTO users SET firstname = :firstname,
                lastname = :lastname,
                sex = :sex,
                roles_id = :roles_id,
                email = :email,
                password = :password;";
            $stmnt = $db->prepare($sql);

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

                $responses->success() && var_dump($responses->getData());

            
                $httpCode = 200;
                $data['status'] = 'success';

            }else {
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
            
        }




/*
        // 3. POST sans ID = Ajout d'une nouvelle entrée
        else if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente
            if (
                isset($_REQUEST['firstname']) && $_REQUEST['firstname'] != ''
                && isset($_REQUEST['lastname']) && $_REQUEST['lastname'] != '' &&
                isset($_REQUEST['sex']) && $_REQUEST['sex'] != ''
                && isset($_REQUEST['password']) && $_REQUEST['password'] != '' &&
                isset($_REQUEST['email']) && $_REQUEST['email'] != ''
                && isset($_REQUEST['roles_id']) && $_REQUEST['roles_id'] != ''
            ) {
                

                //Est-ce que l'email est valide ?
                if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {


                    if (!$this->passwordCheck($_REQUEST['password'])) {
                        $data['status'] = 'error';
                        $data['code'] = 'passFormat';
                        $data['message'] = 'Désolé, mdp invalide: uppercse+lowercase+number+>10';
                    } else {
                        // je crée ma requête et la prépare
                        $sql = "INSERT INTO users SET firstname = :firstname,
                        lastname = :lastname,
                        sex = :sex,
                        roles_id = :roles_id,
                        email = :email,
                        password = :password;";
                        $stmnt = $db->prepare($sql);

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

                        //on hash le mot de passe pour le protéger
                        $password_hashed = password_hash($_REQUEST['password'],  PASSWORD_DEFAULT);

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
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: email, password, role_id, sex, lastname, firstname';
            }
        }


*/

        // 4. PUT/PATCH avec ID = Modification de l'entée X



        else if (($request->getMethod() == 'PATCH' || $request->getMethod() == 'PUT') && isset($args['id'])) {
            // je vérifie que l'id est bien numérique

            parse_str(file_get_contents('php://input'), $_PUT);


            if (is_numeric($args['id'])) {
                // je vérifie que toutes les entrées requises sont bien présentes
                if (
                    isset($_PUT['firstname']) && $_PUT['firstname'] != ''
                    && isset($_PUT['lastname']) && $_PUT['lastname'] != '' 
                    && isset($_PUT['sex']) && $_PUT['sex'] != ''
                    && isset($_PUT['email']) && $_PUT['email'] != ''
                    && isset($_PUT['roles_id']) && $_PUT['roles_id'] != ''
                ) {

                    //Est-ce que l'email est valide ?
                    if (filter_var($_PUT['email'], FILTER_VALIDATE_EMAIL)) {
                        /*$uppercase = preg_match('@[A-Z]@', $_REQUEST['password']);
                    $lowercase = preg_match('@[a-z]@', $_REQUEST['password']);
                    $number    = preg_match('@[0-9]@', $_REQUEST['password']);

                    if(!$uppercase || !$lowercase || !$number || strlen($_REQUEST['password']) < 8) {*/
                      
                            // j'écrit ma requête et la prépare

                            if (isset($_PUT["language_id"])) {
                                $sql = "UPDATE `users` SET 
                                firstname = :firstname,
                                lastname = :lastname,
                                sex = :sex,
                                roles_id = :roles_id,
                                email = :email,
                                language_id= :language_id
                                WHERE id_users = :user_id;";
    
                                $stmnt = $db->prepare($sql);
    
                                $stmnt->bindValue(":language_id", $_PUT['language_id'], PDO::PARAM_INT);
                            }
                            else {
                                $sql = "UPDATE `users` SET 
                                firstname = :firstname,
                                lastname = :lastname,
                                sex = :sex,
                                roles_id = :roles_id,
                                email = :email
                                WHERE id_users = :user_id;";

                                $stmnt = $db->prepare($sql);
                            }

                            

                            //var_dump($_PUT);
                            //var_dump($args["id"]);


                            // je passe les différentes paramètres à ma requête.
                            $stmnt->bindValue(":firstname", $_PUT['firstname'], PDO::PARAM_STR);
                            $stmnt->bindValue(":lastname", $_PUT['lastname'], PDO::PARAM_STR);
                            $stmnt->bindValue(":sex", $_PUT['sex'], PDO::PARAM_STR);
                            $stmnt->bindValue(":roles_id", $_PUT['roles_id'], PDO::PARAM_STR);
                            $stmnt->bindValue(":email", $_PUT['email'], PDO::PARAM_STR);
                            $stmnt->bindValue(":user_id", $args["id"], PDO::PARAM_INT);



                            // Exécution de la requête
                            $stmnt->execute();

                            if ($stmnt/* && $stmnt->rowCount() > 0*/) {


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
                    $data['message'] = 'Désolé, tous les paramètres sont obligatoire: firstname, lastname, email, sex, role_id';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'idMustBeNumeric';
                $data['content'] = 'Désolé l\'id doit être numérique.';
            }
        }
        // 5. DELETE avec ID = Suppression de l'entrée X
        else if ($request->getMethod() == 'DELETE' && isset($args['id'])) {
            // je vérifie que l'ID est bien numérique
            if (is_numeric($args['id'])) {
                // j'écris ma requête et la prépare
                $sql = "DELETE 
                       FROM users 
                       WHERE id_users= :user_id";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":user_id", $args['id'], PDO::PARAM_INT);

                // Exécution de la requête
                $stmnt->execute();

                if ($stmnt && $stmnt->rowCount() > 0) {
                    $httpCode = 200;
                    $data['status'] = 'success';
                } else {
                    $data['status'] = 'error';
                    $data['code'] = 'sqlProblem';
                    $data['content'] = 'Désolé impossible d\'exécuter la requête...';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'idMustBeNumeric';
                $data['content'] = 'Désolé l\'id doit être numérique.';
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
    private function passwordCheck($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);

        if (!$uppercase || !$lowercase || !$number || strlen($password) < 10) {
            return false;
        } else {
            return true;
        }
    }

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
