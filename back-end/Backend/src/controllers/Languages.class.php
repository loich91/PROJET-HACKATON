<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class LanguagesController
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
            FROM language;";
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
        
        // 3. POST sans ID = Ajout d'une nouvelle entrée
        else if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente
            if (
                isset($_REQUEST['name']) && $_REQUEST['name'] != ''
            ) {
                
                        // je crée ma requête et la prépare
                        $sql = "INSERT INTO language SET name = :name";
                        $stmnt = $db->prepare($sql);

                        

                        // je passe à ma requête les différentes paramètres requis
                        $stmnt->bindValue(":name", $_REQUEST['name'], PDO::PARAM_STR);
                       

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
                $data['code'] = 'paramMissing';
                $data['message'] = 'Désolé, le paramètre name es t requis';
            }
        }
 // 5. DELETE avec ID = Suppression de l'entrée X
        else if ($request->getMethod() == 'DELETE' && isset($args['id'])) {
            // je vérifie que l'ID est bien numérique
            if (is_numeric($args['id'])) {
                // j'écris ma requête et la prépare
                $sql = "DELETE 
                       FROM language 
                       WHERE id_language= :id_language";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":id_language", $args['id'], PDO::PARAM_INT);

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
