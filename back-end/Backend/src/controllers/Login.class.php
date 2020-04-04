<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;



//JWT
use \Firebase\JWT\JWT;
use Tuupola\Base62;

class LoginController
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


        // le status par défaut est une erreur
        $data = array();
        $data['status'] = 'error';
        $httpCode = 200;

        $datar = json_decode(file_get_contents("php://input"));

        
        $_REQUEST['email'] = $datar->email;
        $_REQUEST['password'] = $datar->password;

        if ($request->getMethod() == "POST") {
            //1) On vérifie que l'on a bien donné en paramètre un email et un mot de passe
            if (
                isset($_REQUEST['email']) && $_REQUEST['email'] != ''
                && isset($_REQUEST['password']) && $_REQUEST['password'] != ''
            ) {
                //2) connexion à la DB
                //on va chercher le mdp crypté
                $db = new MyPDO();
                $sql = "SELECT * FROM users WHERE email=:email";
                $sth = $db->prepare($sql);
                $sth->bindValue(':email', $_REQUEST['email'], PDO::PARAM_STR);
                $sth->execute();

                //3) on vérifie que l'email existe
                if ($sth && $sth->rowCount() == 1) {
                    $result = $sth->fetch();

                    //4) je verifie le mot de passe en clair avec le mdp de la DB
                    if (password_verify($_REQUEST['password'], $result["password"])) {

                        //5)Génération du token
                        $now = new DateTime(); //Date de début de validité
                        $future = new DateTime("now +2 hours"); //Date de fin de validité
                        $jti = (new Base62)->encode(random_bytes(16)); //chaine aléatoire

                        $payload = [
                            "iat"    => $now->getTimeStamp(),
                            "exp"    => $future->getTimeStamp(),
                            "jti"    => $jti,
                            "user"   => ["user_id" => $result["id_users"],  "role_id" => $result["roles_id"]]
                        ];

                        //clef secrète de notre app (doit être identique à celle dans /public/index.php)
                        $secret = "thisIsACustomKey458SecretA";

                        //création du token
                        $token = JWT::encode($payload, $secret, "HS256");


                        //on envoie les infos à l'utilisateur
                        $data["token"] = $token;
                        $data["status"] = "success";
                        $data["user"] = [
                            "user_id" => $result["id_users"],
                            "firstname" => $result["firstname"],
                            "lastname" => $result["lastname"],
                            "role_id" => $result["roles_id"]
                        ];

                        $data["expires"] = $future->getTimeStamp();

                        $httpCode = 200;
                    } else {
                        $data['message'] = 'Erreur de connexion: identifiants incorects';
                    }
                } else {
                    $data['message'] = 'Erreur de connexion: identifiants incorects';
                }
            } else {
                $data['message'] = 'Donnez les params email et password';
            }
        } else {
            $data['message'] = 'Mauvaise méthode: la méthode doit être POST';
        }




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
