document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".bpb-button");

    buttons.forEach(button => {
        button.addEventListener("click", function (e) {
            const phone = this.getAttribute("href");
            console.log("Calling: " + phone);
            // اینجا می‌تونی ردیابی آنالیتیکس هم اضافه کنی
        });
    });
});
