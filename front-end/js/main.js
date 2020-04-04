

question=document.getElementById("question").innerHTML = "hello";
reponse=document.getElementById("reponse").innerHTML="comment ça va?";
reponseJuste=document.getElementById("reponseJuste");
reponse=document.getElementById("reponse");
point=0


reponse.addEventListener("click", function (e) {

    point=point+1
    reponseCorrect=document.getElementById("point").innerHTML=point;
    reponseJuste=document.getElementById("reponseJuste").innerHTML="je vais bien";

    e.preventDefault();
});






