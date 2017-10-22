/*
function myFunction() {
    document.getElementById("myDropdown").classList.toggle("show");
    var myDropdown = document.getElementById("myDropdown");
    console.log(myDropdown);

}

window.onclick = function(e) {
    if (!e.target.matches('.dropbtn')) {
        var myDropdown = document.getElementById("myDropdown");
        if (myDropdown.classList.contains('show')) {
            myDropdown.classList.remove('show');
        }
    }
}*/


//modal
var modal = document.getElementById('id01');

//closing
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}