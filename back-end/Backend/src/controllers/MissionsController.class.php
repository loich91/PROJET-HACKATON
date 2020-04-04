<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class MissionsController
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
            //var_dump("user_id");

            if($connected_user["roles_id"]==1)
            {
                $sql = "    SELECT mission_id, DATE_FORMAT(date_mission, '%d/%m/%Y %H:%i') AS date_mission, CONCAT(US.firstname,' ',US.lastname)  AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                            FROM missions AS M
                            LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                            LEFT JOIN users AS U ON U.id_users = M.user_id
                            LEFT JOIN language AS L ON M.language_id = L.id_language
                            WHERE M.mission IS NULL;";
                $stmnt = $db->prepare($sql);
            }
            elseif($connected_user["roles_id"]==2)
            {
                $sql = "    SELECT mission_id, DATE_FORMAT(date_mission, '%d/%m/%Y %H:%i') AS date_mission, CONCAT(US.firstname,' ',US.lastname)  AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                            FROM missions AS M 
                            LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                            LEFT JOIN users AS U ON U.id_users = M.user_id
                            LEFT JOIN language AS L ON M.language_id = L.id_language
                            WHERE user_id=:user_id AND M.mission IS NULL ;";
            
            
                $stmnt = $db->prepare($sql);
                $stmnt->bindValue(":user_id",  $connected_user["id_users"]  ,  PDO::PARAM_INT);
            }
            elseif($connected_user["roles_id"]==3)
            {
                $sql = "SELECT mission_id, DATE_FORMAT(date_mission, '%d/%m/%Y %H:%i') AS date_mission, CONCAT(US.firstname,' ',US.lastname)  AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.beneficiare
                        FROM missions AS M 
                        LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                        LEFT JOIN users AS U ON U.id_users = M.user_id
                        LEFT JOIN language AS L ON M.language_id = L.id_language
                        WHERE  M.language_id=:language_id AND M.sex IN('—',:sexuser) AND M.mission IS NULL AND (M.interpreter_id=:connected_user_id OR M.interpreter_id IS NULL); " ;
            
            
                $stmnt = $db->prepare($sql);
                $stmnt->bindValue(":language_id",  $connected_user["language_id"]  ,  PDO::PARAM_INT);
                $stmnt->bindValue(":connected_user_id",  $connected_user["id_users"]  ,  PDO::PARAM_INT);
                $stmnt->bindValue(":sexuser",  $connected_user["sex"]  ,  PDO::PARAM_STR);
            }
            else
            {
                $data['status'] = 'error';
                $data['code'] = 'invalidUserId';
                $data['message'] = 'user_id invalide';
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
        // 3. GET avec ID = détail de l'entrée X
        else if($request->getMethod() == 'GET' && isset($args['id']))
        {
           
            // je vérifie que l'ID est bien numérique
            if(is_numeric($args['id']))
            {
                // je prépare ma requête et l'exécute
                $sql = "SELECT mission_id, DATE_FORMAT(date_mission, '%d/%m/%Y %H:%i') AS date_mission, CONCAT(US.firstname,' ',US.lastname)        AS interpreter , CONCAT(U.firstname,' ',U.lastname) AS partner, L.name AS language, M.sex AS sex_demand, M.status, M.description, M.adress, M.mission, M.beneficiare, M.name AS specialist, M.specialite AS speciality , M.comment AS comment
                        FROM missions AS M
                        LEFT JOIN users AS US ON US.id_users = M.interpreter_id
                        LEFT JOIN users AS U ON U.id_users = M.user_id
                        LEFT JOIN language AS L ON M.language_id = L.id_language 
                        WHERE mission_id = :mission_id;";

                $stmnt = $db->prepare($sql);


                // on passe l'id à notre requête préparée...
                $stmnt->bindValue("mission_id", $args['id'], PDO::PARAM_INT); 

                // Exécution de la requête
                $stmnt->execute();

                // je vérifie que tout est ok, et retourne la réponse
                // je vérifie aussi qu'il y a bien un résultat, sinon 
                // je retourne une erreur
                if($stmnt)
                {
                    $result = $stmnt->fetch(PDO::FETCH_ASSOC);

                    if($result)
                    {
                        $httpCode = 200;
                        $data['status'] = 'success';
                        $data['content'] = $result;
                    }
                    else
                    {
                        $data['status'] = 'error';
                        $data['code'] = 'noEntry';
                        $data['content'] = 'L\'entée ' . $args['id'] . ' ne retourne aucun résultat... ';
                    }
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
        // 4. POST sans ID = Ajout d'une nouvelle entrée
        else if($request->getMethod() == 'POST' && !isset($args['id']))
        {
            // je vérifie que les données que je souhaite sont bien présente
            if( isset($_REQUEST['date_mission']) && $_REQUEST['date_mission'] != ''
                //&& isset($_REQUEST['mission']) && $_REQUEST['mission'] != ''
                && isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != ''
                && isset($_REQUEST['mission_location']) && $_REQUEST['mission_location'] != ''
                //&& isset($_REQUEST['adress']) && $_REQUEST['adress'] != ''
                //&& isset($_REQUEST['description']) && $_REQUEST['description'] != ''
                && isset($_REQUEST['name']) && $_REQUEST['name'] != ''
                //&& isset($_REQUEST['specialite']) && $_REQUEST['specialite'] != ''
                //&& isset($_REQUEST['note']) && $_REQUEST['note'] != ''
                && isset($_REQUEST['beneficiare']) && $_REQUEST['beneficiare'] != ''
                //&& isset($_REQUEST['interpreter_id']) && $_REQUEST['interpreter_id'] != ''
                && isset($_REQUEST['language_id']) && $_REQUEST['language_id'] != ''
                && isset($_REQUEST['sex']) && $_REQUEST['sex'] != ''
                //&& isset($_REQUEST['status']) && $_REQUEST['status'] != ''
            )
            {
                $connected_user=getUserData($_REQUEST["user_id"],$db);
                $_REQUEST=fillMissingParamPost($_REQUEST);

                // je crée ma requête et la prépare
                $sql = "INSERT INTO missions SET 
                       
                        date_mission=TIMESTAMP(:date_mission),
                        mission=:mission,
                        user_id=:user_id,
                        mission_location=:mission_location,
                        adress=:adress,
                        description=:description,
                        name=:name,
                        specialite =:specialite ,
                        note=:note,
                        beneficiare=:beneficiare,
                        interpreter_id=:interpreter_id,
                        language_id=:language_id,
                        sex=:sex,
                        status=:status;";

                /*$sql = "INSERT INTO missions SET 
                       
                        date_mission=TIMESTAMP(:date_mission),
                        mission=:mission,
                        user_id=:user_id,
                        mission_location=:mission_location,
                        adress=:adress,
                        description=:description,
                        name=:name,
                        specialite =:specialite ,
                        note=:note,
                        beneficiare=:beneficiare,
                        interpreter_id=:interpreter_id,
                        language_id=:language_id,
                        sex=:sex,
                        status=:status,
                        comment=:comment;";*/

                    
                        

                    //autre manière
                    //INSERT INTO missions (name,description) VALUES(:name,:des)
                $stmnt = $db->prepare($sql);
                // je passe à ma requête les différentes paramètres requis
              
                $stmnt->bindValue(":date_mission",date("Y-m-d H:i:s",$_REQUEST['date_mission'])  ,       PDO::PARAM_STR );                
                $stmnt->bindValue(":mission",   $_REQUEST['mission']  ,    PDO::PARAM_STR);
                $stmnt->bindValue(":adress",     $_REQUEST['adress']  ,     PDO::PARAM_STR);
                $stmnt->bindValue(":description",        $_REQUEST['description']  ,        PDO::PARAM_STR);
                $stmnt->bindValue(":name", $_REQUEST['name']  , PDO::PARAM_STR);
                $stmnt->bindValue(":specialite", $_REQUEST['specialite']  , PDO::PARAM_STR);
                $stmnt->bindValue(":note", $_REQUEST['note']  , PDO::PARAM_STR);
                $stmnt->bindValue(":beneficiare", $_REQUEST['beneficiare']  , PDO::PARAM_STR);
                $stmnt->bindValue(":interpreter_id", $_REQUEST['interpreter_id']  , PDO::PARAM_INT);
                $stmnt->bindValue(":language_id", $_REQUEST['language_id']  , PDO::PARAM_INT);
                $stmnt->bindValue(":sex", $_REQUEST['sex']  , PDO::PARAM_STR);
                $stmnt->bindValue(":status", $_REQUEST['status']  , PDO::PARAM_STR);
                //$stmnt->bindValue(":comment", $_REQUEST['comment']  , PDO::PARAM_STR);

                if($connected_user["roles_id"]==1 && isset($_REQUEST['partner_id']))
                {
                    $stmnt->bindValue(":user_id", $_REQUEST['partner_id']  , PDO::PARAM_INT);
                }
                else 
                {
                    $stmnt->bindValue(":user_id", $_REQUEST['user_id']  , PDO::PARAM_INT);
                }
                $stmnt->bindValue(":mission_location", $_REQUEST['mission_location']  , PDO::PARAM_STR);

                // Exécution de la requête
                $stmnt->execute();

                if($stmnt)
                {
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
                $data['code'] = 'paramMissing';
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: name, description, taille, id_colors,quantity,price,id_softwares';
            }

        }
        // 5. PUT/PATCH avec ID = Modification de l'entée X
        else if(($request->getMethod() == 'PUT' || $request->getMethod() == 'PATCH') && isset($args['id']))
        {
            parse_str(file_get_contents('php://input'), $_REQUEST);

            $connected_user=getUserData($_REQUEST["user_id"],$db);
            

            if(isset($_REQUEST["status"]))
            {
                if($_REQUEST["status"]=="accepted" && $connected_user["roles_id"]==3)
                {
                    $_REQUEST["interpreter_id"]=$connected_user["id_users"];
                }
            }

            $request=fillMissingParam($db,$args['id'],$_REQUEST);

            if(isset($_REQUEST["cancel"]) && $_REQUEST["cancel"])
            {
                $request["interpreter_id"]=null;
            }

            // je vérifie que l'id est bien numérique
            if(is_numeric($args['id']))
            {
               
                // j'écrit ma requête et la prépare
                $sql = "UPDATE `missions` SET 
                        
                        date_created=:date_created,
                        date_mission=:date_mission,
                        mission=:mission,
                        user_id=:user_id,
                        mission_location=:mission_location,
                        adress=:adress,
                        description=:description,
                        name=:name,
                        specialite =:specialite ,
                        note=:note,
                        beneficiare=:beneficiare,
                        interpreter_id=:interpreter_id,
                        language_id=:language_id,
                        sex=:sex,
                        status=:status,
                        updated=:updated
                        WHERE `mission_id` = :mission_id;";

                $stmnt = $db->prepare($sql);

                // je passe les différentes paramètres à ma requête.
                $stmnt->bindValue(":mission_id",  $args['id'],PDO::PARAM_INT);
                $stmnt->bindValue(":date_created",  $request['date_created'],PDO::PARAM_STR);
                $stmnt->bindValue(":date_mission",  $request['date_mission'],PDO::PARAM_STR);
                $stmnt->bindValue(":mission",  $request['mission'],PDO::PARAM_STR);
                $stmnt->bindValue(":adress",  $request['adress'],PDO::PARAM_STR);
                $stmnt->bindValue(":description",  $request['description'],PDO::PARAM_STR);
                $stmnt->bindValue(":name",  $request['name'],PDO::PARAM_STR);
                $stmnt->bindValue(":specialite",  $request['specialite'],PDO::PARAM_STR);
                $stmnt->bindValue(":note",  $request['note'],PDO::PARAM_STR);
                $stmnt->bindValue(":beneficiare",  $request['beneficiare'],PDO::PARAM_STR);
                $stmnt->bindValue(":interpreter_id",  $request['interpreter_id'],PDO::PARAM_INT);
                $stmnt->bindValue(":language_id",  $request['language_id'],PDO::PARAM_INT);
                $stmnt->bindValue(":sex",  $request['sex'],PDO::PARAM_STR);
                $stmnt->bindValue(":status",  $request['status'],PDO::PARAM_STR);
                $stmnt->bindValue(":updated",  $request['updated'],PDO::PARAM_STR);
                $stmnt->bindValue(":user_id",  $request['user_id'],PDO::PARAM_INT);
                $stmnt->bindValue(":mission_location",  $request['mission_location'],PDO::PARAM_STR);




                // Exécution de la requête
                $stmnt->execute();

                

                if($stmnt)
                {
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
        // 6. DELETE avec ID = Suppression de l'entrée X
        else if($request->getMethod() == 'DELETE' && isset($args['id']))
        {
           // je vérifie que l'ID est bien numérique
           if(is_numeric($args['id']))
           {
               // j'écris ma requête et la prépare
               $sql = "DELETE 
                       FROM missions 
                       WHERE mission_id = :mission_id";

               $stmnt = $db->prepare($sql);

                if($stmnt)
                {
                    // on passe l'id à notre requête préparée...
                    $stmnt->bindValue(":mission_id", $args['id'], PDO::PARAM_INT);

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

/*function getUserData($id_user,$db)
{
        $sql="  SELECT * 
                FROM users 
                WHERE id_users=:id_users;";
        $stmnt = $db->prepare($sql);

        $stmnt->bindValue(":id_users",  $id_user  ,  PDO::PARAM_INT);
        
        $stmnt->execute();
        return $stmnt->fetch(PDO::FETCH_ASSOC);
}*/

function fillMissingParamPost($request)
{
    if(!isset($request["mission"])||$request["mission"]=='') 
    $request["mission"]=NULL;
    if(!isset($request["adress"])||$request["adress"]=='') 
    $request["adress"]=NULL;
    if(!isset($request["specialite"])||$request["specialite"]=='') 
    $request["specialite"]=NULL;
    if(!isset($request["note"])||$request["note"]=='') 
    $request["note"]=NULL;
    if(!isset($request["status"])||$request["status"]=='') 
    $request["status"]="attente";    
    if(!isset($request["user_id"])||$request["user_id"]=='') 
    $request["user_id"]=NULL; 
    if(!isset($request["interpreter_id"])||$request["interpreter_id"]=='') 
    $request["interpreter_id"]=NULL; 
    return $request;
}

function fillMissingParam($db,$mission_id,$request)
{
    $sql = "SELECT * , DATE_FORMAT(date_mission, '%d/%m/%Y %H:%i:')
            FROM missions
            WHERE mission_id=:mission_id";

    $stmnt = $db->prepare($sql);

    $stmnt->bindValue(":mission_id",  $mission_id  ,  PDO::PARAM_INT);
    $stmnt->execute();
    $get=$stmnt->fetch(PDO::FETCH_ASSOC);
    

    foreach ($get as $key => $value) 
    {
        if(!isset($request[$key]))
        {
            $request[$key]=$value;
        }
    }
    $request["user_id"]=$get["user_id"];

    

    return $request;
}
?>

