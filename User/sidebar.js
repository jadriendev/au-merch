const menuBtn = document.getElementById('menuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const menuIcon = document.getElementById('menuIcon');

    menuBtn.addEventListener('click', () => {

        const isClosed = mobileMenu.classList.contains('max-h-0');

        if (isClosed) {

            mobileMenu.classList.remove('max-h-0', 'opacity-0');
            mobileMenu.classList.add('max-h-[500px]', 'opacity-100');

            menuIcon.classList.remove('fa-bars');
            menuIcon.classList.add('fa-xmark');

        } else {

            mobileMenu.classList.remove('max-h-[500px]', 'opacity-100');
            mobileMenu.classList.add('max-h-0', 'opacity-0');

            menuIcon.classList.remove('fa-xmark');
            menuIcon.classList.add('fa-bars');

        }

    });