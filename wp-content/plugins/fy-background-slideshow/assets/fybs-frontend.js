document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-fybs]').forEach(el => {
        const images = el.dataset.images.split(',');
        const duration = parseInt(el.dataset.duration, 10) || 5000;
        const effect = el.dataset.effect || 'fade';
        let index = 0;

        const preload = new Image();
        preload.src = images[0];

        // Initial state
        el.style.backgroundImage = `url(${images[0]})`;
        el.style.backgroundSize = 'cover';
        el.style.backgroundPosition = 'center';
        el.style.transition = 'background-image 1s ease-in-out';

        if (effect === 'slide-left' || effect === 'slide-top') {
            el.style.overflow = 'hidden';
            el.style.backgroundRepeat = 'no-repeat';
            el.style.backgroundSize = 'cover';
            el.style.transition = 'none';
            el.style.position = 'relative';
        }

        setInterval(() => {
            index = (index + 1) % images.length;
            const nextImage = `url(${images[index]})`;

            switch (effect) {
                case 'slide-left':
                    // Simuler slide avec un fond temporaire
                    el.style.transition = 'none';
                    el.style.backgroundPosition = '100% 0';
                    el.style.backgroundImage = nextImage;
                    el.offsetHeight; // trigger reflow
                    el.style.transition = 'background-position 2s ease-in-out';
                    el.style.backgroundPosition = '0 0';
                    break;

                case 'slide-top':
                    el.style.transition = 'none';
                    el.style.backgroundPosition = '0 100%';
                    el.style.backgroundImage = nextImage;
                    el.offsetHeight;
                    el.style.transition = 'background-position 2s ease-in-out';
                    el.style.backgroundPosition = '0 0';
                    break;

                default: // fade
                    el.style.transition = 'background-image 2s ease-in-out';
                    el.style.backgroundImage = nextImage;
                    break;
            }
        }, duration);
    });
});
