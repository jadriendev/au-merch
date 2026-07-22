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

const track = document.getElementById("carouselTrack");

const slides = track.children;
const total = slides.length;

let index = 0;

function getSlideWidth() {
    return track.parentElement.clientWidth;
}

setInterval(() => {

    index++;

    const slideWidth = getSlideWidth();

    track.style.transform = `translateX(-${index * slideWidth}px)`;

    if (index === total - 1) {

        setTimeout(() => {

            track.style.transition = "none";

            index = 0;

            track.style.transform = `translateX(0px)`;

            track.offsetHeight;

            track.style.transition = "";

        }, 700);

    }

}, 3000);