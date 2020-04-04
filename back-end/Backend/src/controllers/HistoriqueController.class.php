<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class HistoriqueController
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

        //$connected_user=getUserData($_REQUEST["user_id"],$db);



        // j'écrit des conditions pour obtenir une des routes REST disponible
        // 1. GET sans ID = liste et utilisateur admin
        // 2. GET sans ID = liste et utilisateur translator
        // 3. GET avec ID = détail de l'entrée X
        // 4. POST sans ID = Ajout d'une nouvelle entrée
        // 5. PUT/PATCH avec ID = Modification de l'entée X
        // 6. DELETE avec ID = Suppression de l'entrée X
        // 7. Aucune route ne correspond, retour erreur REST

        // 1. GET sans ID = liste        
        if($request->getMethod() == 'GET' && !isset($args['id']))
        {

            $connected_user=getUserData($_REQUEST["user_id"],$db);
            
            if($connected_user["roles_id"]==1)
            {
                $sql = "SELECT mission_id, date_mission, CONCAT(US.firstname,' ',US.lastname)  AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                        FROM missions AS M
                        LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                        LEFT JOIN users AS U ON U.id_users = M.user_id
                        LEFT JOIN language AS L ON M.language_id = L.id_language
                        WHERE M.mission IS NOT NULL";
                $stmnt = $db->prepare($sql);
            }
            elseif($connected_user["roles_id"]==2)
            {
                $sql = "SELECT mission_id, date_mission, CONCAT(US.firstname,' ',US.lastname)  AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                        FROM missions AS M
                        LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                        LEFT JOIN users AS U ON U.id_users = M.user_id
                        LEFT JOIN language AS L ON M.language_id = L.id_language
                        WHERE user_id=:user_id  AND M.mission IS NOT NULL ;";
            
            
                $stmnt = $db->prepare($sql);
                $stmnt->bindValue(":user_id",  $connected_user["id_users"]  ,  PDO::PARAM_INT);
            }
            elseif($connected_user["roles_id"]==3)
            {
                $sql = "SELECT mission_id, date_mission, CONCAT(US.firstname,' ',US.lastname)  AS   interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                        FROM missions AS M
                        LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                        LEFT JOIN users AS U ON U.id_users = M.user_id
                        LEFT JOIN language AS L ON M.language_id = L.id_language
                        WHERE  interpreter_id=:user_id  AND M.mission IS NOT NULL" ;
            
                $stmnt = $db->prepare($sql);
                $stmnt->bindValue(":user_id",  $connected_user["id_users"]  ,  PDO::PARAM_INT);
            }
            else
            {
                $data['status'] = 'error';
                $data['code'] = 'userId';
                $data['message'] = 'veuillez passer user_id en paramètre';
            }
            
            $stmnt->execute();

            // je vérifie que tout est ok, et retourne la réponse
            if($stmnt)
            {
                $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

                $httpCode = 200;
                $data['status'] = 'success';
                $data['content'] = $result;
            }
            else
            {
                $data['status'] = 'error';
                $data['code'] = 'requestError';
            }
        }
       
        // 6. DELETE avec ID = Suppression de l'entrée X
        else if($request->getMethod() == 'DELETE' && isset($args['id']))
        {
           // je vérifie que l'ID est bien numérique
           if(is_numeric($args['id']))
           {
               // j'écris ma requête et la prépare
               $sql = "DELETE 
                       FROM missions 
                       WHERE request_id = :request_id";

               $stmnt = $db->prepare($sql);

                if($stmnt)
                {
                    // on passe l'id à notre requête préparée...
                    $stmnt->bindValue(":request_id", $args['id'], PDO::PARAM_INT);

                    // Exécution de la requête
                    $stmnt->execute();

                    $httpCode = 200;
                    $data['status'] = 'success';
                }
               else
               {
                   $data['status'] = 'error';
                   $data['code'] = 'sqlProblem';
                   $data['content'] = 'Désolé impossible d\'exécuter la requête...';
               }
           }
           else
           {
               $data['status'] = 'error';
               $data['code'] = 'idMustBeNumeric';
               $data['content'] = 'Désolé l\'id doit être numérique.';
           }
        }
        else
        {
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
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Headers', '*')
                    ->withHeader('Access-Control-Allow-Methods', '*')
                    ->withStatus($httpCode);
    }
}

function getUserData($id_user,$db)
{
        $sql="  SELECT * 
                FROM users 
                WHERE id_users=:id_users;";
        $stmnt = $db->prepare($sql);

        $stmnt->bindValue(":id_users",  $id_user  ,  PDO::PARAM_INT);
        
        $stmnt->execute();
        return $stmnt->fetch(PDO::FETCH_ASSOC);
}


?>

