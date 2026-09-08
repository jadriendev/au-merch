const slider = document.getElementById('heroSlider');

let currentSlide = 0;
const slideWidth = 500;
const totalSlides = slider.children.length;

setInterval(() => {

    currentSlide++;

    slider.style.transform =
        `translateX(-${currentSlide * slideWidth}px)`;

    // Kapag nasa cloned first image na
    if (currentSlide === totalSlides - 1) {

        setTimeout(() => {

            slider.style.transition = 'none';
            currentSlide = 0;

            slider.style.transform =
                `translateX(0)`;

            // ibalik ang animation
            setTimeout(() => {
                slider.style.transition =
                    'transform 700ms ease-in-out';
            }, 50);

        }, 700);
    }

}, 3000);