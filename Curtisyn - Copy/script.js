// ================= FORM VALIDATION =================

document.addEventListener("DOMContentLoaded", function(){

    const form = document.querySelector("form");
    const name = document.querySelector("input[type='text']");
    const phone = document.querySelector("input[type='tel']");
    const product = document.querySelector("select");
    const message = document.querySelector("textarea");

    form.addEventListener("submit", function(e){

        // prevent page reload
        e.preventDefault();

        // trim values
        let nameValue = name.value.trim();
        let phoneValue = phone.value.trim();
        let productValue = product.value;
        let messageValue = message.value.trim();

        // phone number validation (10 digit)
        let phonePattern = /^[0-9]{10}$/;

        // check empty fields
        if(nameValue === "" || phoneValue === "" || messageValue === "" || productValue === "Select Product"){
            alert("Please fill all fields properly!");
            return;
        }

        // phone validation
        if(!phonePattern.test(phoneValue)){
            alert("Enter valid 10 digit phone number!");
            return;
        }

        // success message
        alert("Inquiry Submitted Successfully!");

        // reset form
        form.reset();
    });

});


// ================= NAVBAR ACTIVE LINK =================
const navLinks = document.querySelectorAll(".nav-link");

navLinks.forEach(link => {
    link.addEventListener("click", function(){
        navLinks.forEach(l => l.classList.remove("active"));
        this.classList.add("active");
    });
});


// ================= SMOOTH SCROLL BUTTON =================
document.querySelectorAll("a[href^='#']").forEach(anchor => {
    anchor.addEventListener("click", function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if(target){
            target.scrollIntoView({
                behavior: "smooth"
            });
        }
    });
});
