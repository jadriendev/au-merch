function toggleFavorite(button) {
    const heart = button.querySelector("i");

    heart.classList.toggle("fa-regular");
    heart.classList.toggle("fa-solid");

    heart.classList.toggle("text-red-500");
    heart.classList.toggle("text-gray-700");
}