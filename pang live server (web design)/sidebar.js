const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const openBtn = document.getElementById("openSidebar");
const closeBtn = document.getElementById("closeSidebar");

openBtn.addEventListener("click", () => {
    sidebar.classList.remove("-translate-x-full");
    overlay.classList.remove("hidden");
});

function closeSidebar() {
    sidebar.classList.add("-translate-x-full");
    overlay.classList.add("hidden");
}

closeBtn.addEventListener("click", closeSidebar);
overlay.addEventListener("click", closeSidebar);

const navbar = document.getElementById("navbar");

window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
        navbar.classList.add(
            "bg-white/90",
            "backdrop-blur-md",
            "shadow-md",
            "border-b",
            "text-black"
        );

        navbar.classList.remove("text-white");
    } else {
        navbar.classList.remove(
            "bg-white/90",
            "backdrop-blur-md",
            "shadow-md",
            "text-black"
        );

        navbar.classList.add("text-white");
    }
});