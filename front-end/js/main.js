ask=document.getElementById("ask").innerHTML="1.Avec quoi puis je désinfecter les pattes de mon chien après sa promenade ?"
answer1=document.getElementById("answer1");
answer2=document.getElementById("answer2");
messageElement = document.querySelector("#message");

reponseU='';
reponseJuste='b';
message='';


answer1.innerHTML="a)Avec de la javel ou du gel hydroalcoolique"
answer2.innerHTML="b)Avec du savon et puis rincer à l’eau";

fetch('http://tonserver.com/question');

function sendResponse(response) {
    fetch('http://tonserver.com/response', {
        method: 'post',
        body: response
    });
    return true;
}

function displayResponse(response) {
    if(response == true) {
        messageElement.innerHTML = "C'est juste";
    } else {
        messageElement.innerHTML = "C'est faux";
    }
}

answer1.addEventListener("click",function (e) {
    response = sendResponse('a');
    displayResponse(response);
});
answer2.addEventListener("click",function (e) {
    response = sendResponse('b');
    displayResponse(response);
});
// if(question!= null && answer1!= null && answer2!= null ){


// answer1.addEventListener("click",function (e) {
//         reponseU='a';
//         if(reponseU == reponseJuste){
//             message=document.getElementById("message").innerHTML="c'est juste"
//             console.log(reponseU)
//             console.log(reponseJuste)
//         }
//         if (reponseU != reponseJuste){
//             message=document.getElementById("message").innerHTML="c'est faux"
//             console.log(reponseU)
//             console.log(reponseJuste)
//         }
//         e.preventDefault();
//     });
//    answer2.addEventListener("click",function (e) {
//         reponseU='b';
//         console.log(reponseJuste)
//         if(reponseU == reponseJuste){
//             message=document.getElementById("message").innerHTML="c'est juste"
//             console.log(reponseU)
//             console.log(reponseJuste)
//         }
//     if (reponseU != reponseJuste){
//             message=document.getElementById("message").innerHTML="c'est faux"
//             console.log(reponseU)
//             console.log(reponseJuste)
//         }
//         e.preventDefault();
//     })
// }





