<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use \Mailjet\Resources;

class NotificationsController
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

        if ($request->getMethod() == 'POST' && isset($args['id'])) {


            if (isset($args['id']) && $args['id'] != '') {

                // je prépare ma requête et l'exécute
                $sql = "SELECT *
                        FROM users 
                        where id_users = :id_users";

                $stmnt = $db->prepare($sql);

                // on passe l'id à notre requête préparée...
                $stmnt->bindValue(":id_users", $args['id'], PDO::PARAM_INT);

                // Exécution de la requête
                $stmnt->execute();


                // je vérifie que tout est ok, et retourne la réponse
                if ($stmnt && $stmnt->rowCount() > 0) {
                    $result = $stmnt->fetch(PDO::FETCH_ASSOC);

                    if ($result['roles_id'] === 1) {


                        $sql1 = "SELECT COUNT(*) AS attente FROM missions WHERE NOW()<=date_mission AND status='attente' AND `interpreter_id` IS NULL";
                        $stmnt1 = $db->prepare($sql1);
                        $stmnt1->execute();
                        $result1 = $stmnt1->fetch(PDO::FETCH_ASSOC);


                        $sql2 = "SELECT COUNT(*) AS encours FROM missions WHERE NOW()<=date_mission AND status='validated' AND `interpreter_id` IS NOT NULL";
                        $stmnt2 = $db->prepare($sql2);
                        $stmnt2->execute();
                        $result2 = $stmnt2->fetch(PDO::FETCH_ASSOC);


                        $sql3 = "SELECT COUNT(*) AS completer FROM missions WHERE NOW()>date_mission AND status = 'validated' AND mission = 'prestée' AND `interpreter_id` IS NOT NULL";
                        $stmnt3 = $db->prepare($sql3);
                        $stmnt3->execute();
                        $result3 = $stmnt3->fetch(PDO::FETCH_ASSOC);




                        $sql4 = "SELECT * , DATE_FORMAT(updated, '%d/%m/%Y') AS DATE_UPDATE_FR, DATE_FORMAT(updated, '%H:%i') AS HEURE_UPDATE_FR, DATE_FORMAT(date_mission, '%d/%m/%Y') AS DATE_MISSION_FR, DATE_FORMAT(date_mission, '%H:%i') AS HEURE_MISSION_FR FROM missions INNER JOIN users ON interpreter_id=id_users WHERE NOW()<date_mission AND `interpreter_id` IS NOT NULL ORDER BY updated DESC LIMIT 0,3";
                        $stmnt4 = $db->prepare($sql4);
                        $stmnt4->execute();
                        $result4 = $stmnt4->fetchAll(PDO::FETCH_ASSOC);
                        $data['notifications'] = $result;

                        $httpCode = 200;

                        $data['status'] = 'success';
                        $data['mission'] = $result1;
                        $data['missions'] = $result2;
                        $data['missionss'] = $result3;
                        $data['notifications'] = $result4;
                    } elseif ($result['roles_id'] === 2) {



                        $sql1 = "SELECT COUNT(*) AS attente FROM missions WHERE NOW()<=date_mission AND status='attente' AND `interpreter_id` IS NULL AND user_id = :id_users";
                        $stmnt1 = $db->prepare($sql1);
                        $stmnt1->bindValue(":id_users", $result['id_users'], PDO::PARAM_INT);
                        $stmnt1->execute();
                        $result1 = $stmnt1->fetch(PDO::FETCH_ASSOC);



                        $sql2 = "SELECT COUNT(*) AS encours FROM missions WHERE NOW()<=date_mission AND status='validated' AND `interpreter_id` IS NOT NULL AND user_id = :id_users";
                        $stmnt2 = $db->prepare($sql2);
                        $stmnt2->bindValue(":id_users", $result['id_users'], PDO::PARAM_INT);
                        $stmnt2->execute();
                        $result2 = $stmnt2->fetch(PDO::FETCH_ASSOC);




                        $sql3 = "SELECT COUNT(*) AS completer FROM missions WHERE NOW()>date_mission AND status = 'validated' AND mission = 'prestée' AND `interpreter_id` IS NOT NULL AND user_id=:id_users";
                        $stmnt3 = $db->prepare($sql3);
                        $stmnt3->bindValue(":id_users", $result['id_users'], PDO::PARAM_INT);
                        $stmnt3->execute();
                        $result3 = $stmnt3->fetch(PDO::FETCH_ASSOC);



                        // SELECT * FROM missions INNER JOIN users ON user_id=id_users WHERE NOW()<date_mission AND `interpreter_id` IS NOT NULL AND user_id=2 ORDER BY updated DESC



                        $sql4 = "SELECT * , DATE_FORMAT(updated, '%d/%m/%Y') AS DATE_UPDATE_FR, DATE_FORMAT(updated, '%H:%i') AS HEURE_UPDATE_FR, DATE_FORMAT(date_mission, '%d/%m/%Y') AS DATE_MISSION_FR, DATE_FORMAT(date_mission, '%H:%i') AS HEURE_MISSION_FR FROM missions INNER JOIN users ON interpreter_id=id_users WHERE NOW()<date_mission AND `interpreter_id` IS NOT NULL AND user_id=:id_users ORDER BY updated DESC LIMIT 0,3";
                        $stmnt4 = $db->prepare($sql4);
                        $stmnt4->bindValue(":id_users", $result['id_users'], PDO::PARAM_INT);
                        $stmnt4->execute();
                        $result4 = $stmnt4->fetchAll(PDO::FETCH_ASSOC);


                        $httpCode = 200;

                        $data['status'] = 'success';
                        $data['mission'] = $result1;
                        $data['missions'] = $result2;
                        $data['missionss'] = $result3;
                        $data['notifications'] = $result4;
                    } elseif ($result['roles_id'] === 3) {


// bien verifier

                        $sql1 = "SELECT COUNT(*) AS attente FROM missions WHERE NOW()<=date_mission AND status='attente' AND `interpreter_id` IS NULL AND language_id=:language_id  AND (sex = :sex OR sex = '-')";
                        $stmnt1 = $db->prepare($sql1);
                        $stmnt1->bindValue(":language_id", $result['language_id'], PDO::PARAM_INT);
                        $stmnt1->bindValue(":sex", $result['sex'], PDO::PARAM_STR);
                        $stmnt1->execute();
                        $result1 = $stmnt1->fetch(PDO::FETCH_ASSOC);



                        $sql2 = "SELECT COUNT(*) AS encours FROM missions WHERE NOW()<=date_mission AND status='validated' AND interpreter_id = :interpreter_id";
                        $stmnt2 = $db->prepare($sql2);
                        $stmnt2->bindValue(":interpreter_id", $result['id_users'], PDO::PARAM_INT);
                        $stmnt2->execute();
                        $result2 = $stmnt2->fetch(PDO::FETCH_ASSOC);




                        $sql3 = "SELECT COUNT(*) AS completer FROM missions WHERE NOW()>date_mission AND status = 'validated' AND mission = 'prestée' AND interpreter_id = :interpreter_id";
                        $stmnt3 = $db->prepare($sql3);
                        $stmnt3->bindValue(":interpreter_id", $result['id_users'], PDO::PARAM_INT);
                        $stmnt3->execute();
                        $result3 = $stmnt3->fetch(PDO::FETCH_ASSOC);



                        // SELECT * FROM missions INNER JOIN users ON user_id=id_users WHERE NOW()<date_mission AND `interpreter_id` IS NOT NULL AND user_id=2 ORDER BY updated DESC



                        $sql4 = "SELECT * , DATE_FORMAT(updated, '%d/%m/%Y') AS DATE_UPDATE_FR, DATE_FORMAT(updated, '%H:%i') AS HEURE_UPDATE_FR, DATE_FORMAT(date_mission, '%d/%m/%Y') AS DATE_MISSION_FR, DATE_FORMAT(date_mission, '%H:%i') AS HEURE_MISSION_FR FROM missions WHERE NOW()<date_mission AND status = 'validated' AND mission IS NULL AND interpreter_id = :interpreter_id ORDER BY updated DESC LIMIT 0,3";
                        $stmnt4 = $db->prepare($sql4);
                        $stmnt4->bindValue(":interpreter_id", $result['id_users'], PDO::PARAM_INT);
                        $stmnt4->execute();
                        $result4 = $stmnt4->fetchAll(PDO::FETCH_ASSOC);

                        $httpCode = 200;

                        $data['status'] = 'success';
                        $data['mission'] = $result1;
                        $data['missions'] = $result2;
                        $data['missionss'] = $result3;
                        $data['notifications'] = $result4;
                    }
                } else {
                    $data['status'] = 'error';
                    $data['code'] = '';
                }
            } else {
                $data['status'] = 'error';
                $data['code'] = 'paramMissing';
                $data['message'] = "Désolé, l'id_users est obligatoire";
            }
        }  else {
            // aucune des routes REST n'a été rencontrée
            // j'informe mon utilisateur qu'il doit respecter la norme
            // REST !
            $method = $request->getMethod();
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
