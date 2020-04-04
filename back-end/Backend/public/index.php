<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathDetector;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;

//require "./cors.php";
//cors();

// ne pas oublier d'inclure la class MyPDO pour effectuer nos requêtes SQL.
// ne pas oublier d'inclure les différents contrôleurs utiliser pour nos routes!

require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/class/MyPDO.class.php';
require __DIR__ . '/../vendor/autoload.php';


// je fait un require de tous les controllers qui sont utilisés dans notre application.
require __DIR__ . '/../src/controllers/Index.class.php';
require __DIR__ . '/../src/controllers/Login.class.php';
require __DIR__ . '/../src/controllers/Users.class.php';
require __DIR__ . '/../src/controllers/Admin.class.php';
require __DIR__ . '/../src/controllers/Create.class.php';
require __DIR__ . '/../src/controllers/Languages.class.php';
require __DIR__ . '/../src/controllers/Password.class.php';
require __DIR__ . '/../src/controllers/Notifications.class.php';



// UNIVERBAL
require __DIR__ . '/../src/controllers/MissionsController.class.php';
//require __DIR__ . '/../src/controllers/TranslatorsController.class.php';
require __DIR__ . '/../src/controllers/PartnersController.class.php';
require __DIR__ . '/../src/controllers/HistoriqueController.class.php';
require __DIR__ . '/../src/controllers/Interpreters.class.php';



// je fait un require de tous les middlewares qui sont utilisés dans notre application.
require __DIR__ . '/../src/middlewares/Right.class.php';

// création d'une instance de slim pour pouvoir l'utiliser par la suite
// ne pas toucher le code qui suit ;-)
$app = AppFactory::create();

//JWT
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//@see https://github.com/tuupola/slim-jwt-auth

/*

$app->add(new Tuupola\Middleware\JwtAuthentication([
    "secret" => "thisIsACustomKey458SecretA",
    "header" => "Authorization",
    "secure" => true,
    "relaxed" => ["localhost"],
    "ignore" => ["/login"],
    "algorithm" => ["HS256"],
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader('Content-Type', '*')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

*/

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


$basePath = (new BasePathDetector($_SERVER))->getBasePath();
$app->setBasePath($basePath);
$callableResolver = $app->getCallableResolver();


// Définition d'un gestionnaire d'erreur pour afficher 404 en cas de problème
// et ne pas afficher un problème au niveau de notre application
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $response = array();
    $response['status'] = 'error';
    $response['code '] = $exception->getCode();
    $payload = ['status' => 'error', 'code' => $exception->getCode(), 'message' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($exception->getCode());
};

// Add Error Middleware
// commenter les 2 lignes suivante si notre application ne fonctionne pas
// pour avoir une erreur plus facilement compréhensible sur ce qui ne va pas
if ($debug === false) {
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
}


// usefull
// https://www.slimframework.com/docs/v4/objects/request.html

// listes des routes utilisées par notre application
// $app->any permet de capturer toutes les méthodes: get, post, put/patch et delete
// le premier paramètre est la route qu'on souhaite captuée
// le second paramètre est le nom du contrôlleur qui va traiter la réponse
$app->any('/', 'IndexController');
$app->any('/login', 'LoginController')->setName('login');

//On groupe les routes pour appliquer le Middleware sur toutes les routes à la fois
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->any('/users[/{id}]', 'UsersController')->setName('users');
    //$group->any('/translators[/{id}]', 'TranslatorsController')->setName('translators');
    $group->any('/partners', 'PartnersController')->setName('partners');
    $group->any('/missions[/{id}]', 'MissionsController')->setName('missions');
    $group->any('/admin[/{id}]', 'AdminController')->setName('admin');
    $group->any('/create[/{id}]', 'CreateController')->setName('create');
    $group->any('/languages[/{id}]', 'languagesController')->setName('languages');
    //$group->any('/admin[/{id}]', 'AdminController')->setName('users');
    $group->any('/historique', 'HistoriqueController')->setName('historique');
    $group->any('/password', 'PasswordController')->setName('password');
    $group->any('/newrecup', 'NewrecupController')->setName('newrecup');
    $group->any('/interpreters', 'InterpretersController')->setName('interpreters');
    $group->any('/notifications[/{id}]', 'NotificationsController')->setName('notifications');
});//->add(new RightMiddleware());



// on indique à slim de lancer son exécution
$app->run();
