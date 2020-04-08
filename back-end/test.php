<?php
$question[1]="1. Avec quoi puis je désinfecter les pattes de mon chien après sa promenade ?|a) Avec de la javel ou du gel hydroalcoolique|b) Avec du savon et puis rincer à l’eau";

$reponseq[0]="Avec de la javel ou du gel hydroalcoolique";
$reponseq[1]="Avec du savon et puis rincer à l’eau";

$note = 0;
if (isset($_POST['nb'])) 
{ 
    $nb=$_POST['nb']; 
}
else
{ 
    $nb=0; 
}

if (isset($_POST['reponseq'])) 
{ 
    $reponseq[0]=$_POST['reponseq'].$_POST['rep']."," ; 
}
else
{ 
    $reponseq[0]=""; 
}

if ($nb < count($question))
{
 ?>
 <form name="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
 <?php  
 $question=explode("|",$question[$nb]);
 echo $question[0]."<br><br>"; 
 for ($nbr_rep=1; $nbr_rep < count($question); $nbr_rep++)
{
 	?>
 	<input type="radio" name="rep" value="<?php echo $nbr_rep; ?>" <?php if ($nbr_rep==1) echo "checked"; ?>><?php echo $question[$nbr_rep]."<br>"; 
}
 ?>
 <br>
 <input type="hidden" name="reponseq" value="<?php echo $reponseq[0]; ?>">
 <input type="hidden" name="nb" value="<?php echo $nb+1; ?>"> 
 <input type="submit" name="envoyer" value="Envoyer"><br> 
 </form>
<?php
}
else
{
$rep=explode(",", $reponseq[0]);
 echo "Terminer<br><br>";
  
  for ($i=0; $i < count($reponseq); $i++)
  {   
    
    $question=explode("|",$question[$i]);
    echo "A la question " . $note . ": <font color=\"FF0000\"> " . $question[0] . "</font><br> Vous avez répondu  " . $question[$rep[$i]] . "<br> reponseq :"  . $reponseq[$i] . "<br><br>";	
  }
}


?>