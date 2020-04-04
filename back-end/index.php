<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test</title>
</head>
<body>
<?php

$ask;
$answer1;
$answer2;
$response;

$ask = array(
    "What does HTML stand for?" => array(
        '1' => "Home Tool Markup Language",
        '2' => "Hyperlinks and Text Markup Language",
        '3' => "Hyper Text Markup Language",
        '4' => "Hyper Text Manipulation Language",
    ),
    "Choose the correct HTML tag for the smallest heading:" => array(
        '2' => "&lt;heading&gt;",
        '1' => "&lt;h1&gt;",
        '4' => "&lt;head&gt;",
        '3' => "&lt;h6&gt;",
    ),
    );
    
    echo "<form>";
    foreach($ask as $response => $answer1) {
        echo  "<p> $response </p>";
        foreach($ask as $reponse => $answer2) {
            echo "<input type\radio" name="=\"$ask\"> <p> $response</p><br/>";
        }
    }
    echo "</form>";
?> 
</body>
</html>


