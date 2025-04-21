function showLoginPopup() {
    const loginPopup = document.getElementById("loginPopup");
    if (loginPopup) {
        loginPopup.style.display = "flex";
        document.body.classList.add("blur");

        fetch("php/get_csrf_token.php")
            .then(response => response.json())
            .then(data => {
                const csrfTokenInput = document.querySelector('#loginForm input[name="csrf_token"]');
                if (csrfTokenInput && data.csrf_token) {
                    csrfTokenInput.value = data.csrf_token;
                } else {
                    console.error("CSRF token input not found or token not received.");
                }

                // Now attach the event listener for form submission AFTER the token is fetched
                const loginForm = document.getElementById("loginForm");
                loginForm.addEventListener("submit", function(event) {
                    event.preventDefault(); // Prevent default form submission

                    const formData = new FormData(loginForm);
                    fetch("php/login.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => {
                        console.log("Raw response:", response); // Debug log
                        return response.json();
                    })
                    .then(responseData => {
                        console.log("Parsed JSON data:", responseData); // Debug log
                        if (responseData.success) {
                            console.log("Redirecting to:", responseData.redirect); // Debug log
                            // If login is successful, redirect to the URL
                            window.location.href = responseData.redirect;
                        } else {
                            // If login fails, show error message in pop-up
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
            })
            .catch(error => {
                console.error("Error fetching CSRF token:", error);
                const loginMessage = document.querySelector(".login-message");
                loginMessage.textContent = "エラーが発生しました。もう一度お試しください。"; // Or a more specific error
                loginMessage.style.color = "red";
            });
    }
}

function hideLoginPopup() {
    console.log("hideLoginPopup function called!");
    const loginPopup = document.getElementById("loginPopup");
    if (loginPopup) {
        loginPopup.style.display = "none";
        document.body.classList.remove("blur");

        // Remove the event listener to prevent multiple attachments (optional, but good practice)
        const loginForm = document.getElementById("loginForm");
        loginForm.removeEventListener("submit", /* the listener function - difficult to get a reference here directly */);
        // A better way is to re-attach the listener every time the popup is shown.
    }
}

function togglePasswordVisibility() {
    const passwordField = document.getElementById("passwordField");
    const viewPasswordCheckbox = document.getElementById("viewPasswordCheckbox");
    if (passwordField && viewPasswordCheckbox) {
        passwordField.type = viewPasswordCheckbox.checked ? "text" : "password";
    } else {
        console.error("Password field or checkbox not found");
    }
}