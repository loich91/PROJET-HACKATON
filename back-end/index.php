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

var_dump(json_decode($json));
?>

</body>
</html>


