const user = JSON.parse(sessionStorage.getItem("user"));

if (user) {
    document.getElementById("welcomeMessage").textContent =
        `Welcome back, ${user.name}!`;
}