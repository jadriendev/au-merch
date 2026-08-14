function togglePassword() {
    const password = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");
    } else {
        password.type = "password";
        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
    }
}


// Login
const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const student_id = loginForm.student_id.value.trim();
    const password = loginForm.password.value;

    // Check empty fields
    if (!student_id || !password) {
        alert("Please enter your Student ID and password.");
        return;
    }

    try {
        const response = await fetch("/api/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                student_id: student_id,
                password: password
            })
        });

        const data = await response.json();

        if (!response.ok) {
            alert(data.message);
            return;
        }

        // Save logged-in user
        sessionStorage.setItem("user", JSON.stringify(data.user));

        // Redirect after successful login
        window.location.href = "User/homepage.html";

    } catch (error) {
        console.error("Login error:", error);
        alert("Unable to connect to the server.");
    }
});