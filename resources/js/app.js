import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


    document.addEventListener('DOMContentLoaded', function () {
        const toTopBtn = document.getElementById('scrollToTopBtn');
        const toBottomBtn = document.getElementById('scrollToBottomBtn');

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY || document.documentElement.scrollTop;
            const scrollable = document.documentElement.scrollHeight - window.innerHeight;

            if (scrollY > 100) {
                toTopBtn.classList.remove('hidden');
            } else {
                toTopBtn.classList.add('hidden');
            }

            if (scrollY + window.innerHeight < scrollable - 100) {
                toBottomBtn.classList.remove('hidden');
            } else {
                toBottomBtn.classList.add('hidden');
            }
        });

        toTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        toBottomBtn.addEventListener('click', () => {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        });
    });