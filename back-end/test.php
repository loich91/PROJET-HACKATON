<?php
$question[0]="John F. Kennedy à été |Un ami personnel de Fidel Castro|Le leader de la révolution américaine|Assassiné en 1963 par Jack Ruby|Assassiné à Dallas en 1963";
$question[1]="Quel président américain fut assassiné en 1865?|Abraham Lincoln|George Washington|Richard Nixon|Andrew Johnson";
$question[2]="Il s'est lui-même fait nommer président à vie d’Haïti. C'est son fils Jean-Claude qui lui a succédé en 1971?|P.E. Trudeau|Richard Hatfield|François Duvalier|Ernesto « Che » Guevara";

$reponseq[0]="Assassiné à Dallas en 1963";
$reponseq[1]="Abraham Lincoln";
$reponseq[2]="François Duvalier";

$note = 0;
if (isset($_POST['nb'])) 
{ 
    $nb=$_POST['nb']; 
}
else
{ 
    $nb=0; 
}

if (isset($_POST['reponse'])) 
{ 
    $reponse[0]=$_POST['reponse'].$_POST['rep']."," ; 
}
else
{ 
    $reponse[0]=""; 
}

if ($nb < count($question))
{
 ?>
 <form name="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
 <?php 
 $laqueston=explode("|",$question[$nb]);
 echo $laqueston[0]."<br><br>"; 
 for ($nbr_rep=1; $nbr_rep < count($laqueston); $nbr_rep++)
{
 	?>
 	<input type="radio" name="rep" value="<?php echo $nbr_rep; ?>" <?php if ($nbr_rep==1) echo "checked"; ?>><?php echo $laqueston[$nbr_rep]."<br>"; 
}
 ?>
 <br>
 <input type="hidden" name="reponse" value="<?php echo $reponse[0]; ?>">
 <input type="hidden" name="nb" value="<?php echo $nb+1; ?>"> 
 <input type="submit" name="envoyer" value="Envoyer"><br> 
 </form>
<?php
}
else
{
$rep=explode(",", $reponse[0]);
 echo "Terminér<br><br>";
  
  for ($i=0; $i <= count($reponse)+1; $i++)
  {   
    
    $laqueston=explode("|",$question[$i]);
    echo "A la question " . $note . ": <font color=\"FF0000\"> " . $laqueston[0] . "</font><br> Vous avez répondu  " . $laqueston[$rep[$i]] . "<br> Reponse :"  . $reponseq[$i] . "<br><br>";	
  }
}


?>