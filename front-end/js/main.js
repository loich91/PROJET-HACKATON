

question=document.getElementById("question").innerHTML = "hello";
reponse=document.getElementById("reponse").innerHTML="comment ça va?";
reponse=document.getElementById("reponse");
point=0


reponse.addEventListener("click", function (e) {
    alert('je vais bien')
    point=point+1
    reponseCorrect=document.getElementById("point").innerHTML=point;


    e.preventDefault();
});






