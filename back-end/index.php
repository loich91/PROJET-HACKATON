
<?php

//header de la réponse (pour éviter les problèmes de CORS)
header("Access-Control-Allow-Origin: *");  
header("Content-Type: application/json; charset=UTF-8"); 
header("HTTP/1.1 200 OK");

// indiqué le chemin de votre fichier JSON, il peut s'agir d'une URL
$json = file_get_contents("response.json");



$parsed_json = json_decode($json);
//var_dump(json_decode($json ));

$ask = $parsed_json->{'quizz'}[0]->{'ask'};
$answer1 = $parsed_json->{'quizz'}[0]->{'answer1'};
$answer2 = $parsed_json->{'quizz'}[0]->{'answer2'};
$reponse= $parsed_json->{'quizz'}[0]->{'reponse'};

$data=[
    "ask"       =>  $ask,
    "answer1"   =>  $answer1,
    "answer2"   =>  $answer2,
    "reponse"   =>  $reponse];
$req_response=json_encode($data);
	
echo $req_response;
