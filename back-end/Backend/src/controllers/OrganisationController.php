<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class OrganisationController
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
                $sql = "SELECT id_organisation, name, adresse, email, phone
                        FROM organisation
                        WHERE id_organisation = :id_organisation;";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":id_organisation", $args['id'], PDO::PARAM_INT);

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
                        $data['content'] = 'L\'entrée ' . $args['id'] . ' ne retourne aucun résultat... ';
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
        // 3. POST sans ID = Ajout d'une nouvelle entrée
        else if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente
            if (
                isset($_REQUEST['id_organisation']) && $_REQUEST['id_organisation'] != ''
                && isset($_REQUEST['name']) && $_REQUEST['name'] != '' &&
                isset($_REQUEST['adress']) && $_REQUEST['adress'] != ''
                && isset($_REQUEST['email']) && $_REQUEST['email'] != ''
                && isset($_REQUEST['phone']) && $_REQUEST['phone'] != ''
            ) {
                //Est-ce que l'email est valide ?
                if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {

                    if (!$this->passwordCheck($_REQUEST['password'])) {
                        $data['status'] = 'error';
                        $data['code'] = 'passFormat';
                        $data['message'] = 'Désolé, mdp invalide: uppercse+lowercase+number+>10';

                    } else {
                        // je crée ma requête et la prépare
                        $sql = "INSERT INTO organisation SET id_organisation = :id_organisation,
                        name = :name,
                        adress = :adress,
                        phone = :phone,
                        email = :email";
                        $stmnt = $db->prepare($sql);


                        // je passe à ma requête les différentes paramètres requis
                        $stmnt->bindValue(":id_organisation", $_REQUEST['id_organisation'], PDO::PARAM_INT);
                        $stmnt->bindValue(":name", $_REQUEST['name'], PDO::PARAM_STR);
                        $stmnt->bindValue(":adress", $_REQUEST['adress'], PDO::PARAM_STR);
                        $stmnt->bindValue(":phone", $_REQUEST['phone'], PDO::PARAM_STR);
                        $stmnt->bindValue(":email", $_REQUEST['email'], PDO::PARAM_STR);

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
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: email, password, role_id, adress, name, id_organisation';
            }
        }
        // 4. PUT/PATCH avec ID = Modification de l'entée X


        else if (($request->getMethod() == 'PATCH' || $request->getMethod() == 'PUT') && isset($args['id'])) {
            // je vérifie que l'id est bien numérique

            parse_str(file_get_contents('php://input'), $_PUT);
            

            if (is_numeric($args['id'])) {
                //var_dump($_REQUEST);
                // je vérifie que toutes les entrées requises sont bien présentes
                if (
                    isset($_PUT['id_organisation']) && $_PUT['id_organisation'] != ''
                    && isset($_PUT['name']) && $_PUT['name'] != '' 
                    && isset($_PUT['adress']) && $_PUT['adress'] != ''
                    && isset($_PUT['email']) && $_PUT['email'] != ''
                    && isset($_PUT['phone']) && $_PUT['phone'] != ''
                ) {

                    //Est-ce que l'email est valide ?
                    if (filter_var($_PUT['email'], FILTER_VALIDATE_EMAIL)) {
                        /*$uppercase = preg_match('@[A-Z]@', $_REQUEST['password']);
                    $lowercase = preg_match('@[a-z]@', $_REQUEST['password']);
                    $number    = preg_match('@[0-9]@', $_REQUEST['password']);

                    if(!$uppercase || !$lowercase || !$number || strlen($_REQUEST['password']) < 8) {*/
                        if (!$this->passwordCheck($_PUT['password'])) {
                            $data['status'] = 'error';
                            $data['code'] = 'passFormat';
                            $data['message'] = 'Désolé, mdp invalide: uppercase+lowercase+number+>10';
                        } else {
                            // j'écrit ma requête et la prépare
                            $sql = "UPDATE `users` SET 
                            id_organisation = :id_organisation,
                            name = :name,
                            adress = :adress,
                            phone = :phone,
                            email = :email,
                            password = :password
                            WHERE id_organisation = :id_organisation;";

                            $stmnt = $db->prepare($sql);

                            //on hash le mot de passe pour le protéger
                            $password_hashed = password_hash($_PUT['password'],  PASSWORD_DEFAULT);

                            // je passe les différentes paramètres à ma requête.
                            $stmnt->bindValue(":id_organisation", $_PUT['id_organisation'], PDO::PARAM_INT);
                            $stmnt->bindValue(":name", $_PUT['name'], PDO::PARAM_STR);
                            $stmnt->bindValue(":adress", $_PUT['adress'], PDO::PARAM_STR);
                            $stmnt->bindValue(":phone", $_PUT['phone'], PDO::PARAM_STR);
                            $stmnt->bindValue(":email", $_PUT['email'], PDO::PARAM_STR);


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
                    $data['message'] = 'Désolé, tous les paramètres sont obligatoire: id_organisation, name, email, adress, password, role_id';
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
                       FROM organisation
                       WHERE id_organisation= :id_organisation";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":id_organisation", $args['id'], PDO::PARAM_INT);

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
            $method = $request->getMethod();
            var_dump($method);
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

    
    
}
