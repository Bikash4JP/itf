function togglePasswordVisibility() {
    const passwordField = document.getElementById("passwordField");
    const viewPasswordCheckbox = document.getElementById("viewPasswordCheckbox");
    if (passwordField && viewPasswordCheckbox) {
        passwordField.type = viewPasswordCheckbox.checked ? "text" : "password";
    } else {
        console.error("Password field or checkbox not found");
    }
}

// Handle form submission on the full page
document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(loginForm);
            fetch("login.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(responseData => {
                if (responseData.success) {
                    window.location.href = responseData.redirect;
                } else {
                    const loginMessage = document.querySelector(".login-message");
                    loginMessage.textContent = responseData.message;
                    loginMessage.style.color = "red";
                }
            })
            .catch(error => {
                console.error("Error:", error);
                const loginMessage = document.querySelector(".login-message");
                loginMessage.textContent = "エラーが発生しました。もう一度お試しください。";
                loginMessage.style.color = "red";
            });
        });
    }
});