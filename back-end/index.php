<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test</title>
</head>
<body>
<?php

// indiqué le chemin de votre fichier JSON, il peut s'agir d'une URL
$json = file_get_contents("response.json");



$parsed_json = json_decode($json);
//var_dump(json_decode($json ));
var_dump ($parsed_json);
$ask = $parsed_json->{'quizz'}[0]->{'ask'};
$answer1 = $parsed_json->{'quizz'}[0]->{'ask'};
$answer2 = $parsed_json->{'quizz'}[0]->{'ask'};
echo $ask ;
echo $answer1;
echo $answer2  ;
?>

</body>
</html>


