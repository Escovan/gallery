document.addEventListener('DOMContentLoaded', () => {
    // --- DARK MODE LOGIC ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme');

    // Functie om thema te updaten
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            if (themeToggleBtn) themeToggleBtn.innerText = '☀️ Light Mode';
        } else {
            document.body.classList.remove('dark-mode');
            if (themeToggleBtn) themeToggleBtn.innerText = '🌙 Dark Mode';
        }
    };

    if (currentTheme) {
        applyTheme(currentTheme);
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-mode');
            const newTheme = isDark ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }


    // --- GALERIJ EN INFINITE SCROLL ---
    const galleryGrid = document.getElementById('gallery-grid');
    const scrollSentinel = document.getElementById('scroll-sentinel');
    let clickableItems = [];
    let galleryImages = window.galleryImages || [];
    let loadedCount = window.initialImageCount || 0;
    const batchSize = 20;

    // Event Delegation voor Lightbox
    if (galleryGrid) {
        galleryGrid.addEventListener('click', (e) => {
            const item = e.target.closest('.gallery-clickable');
            if (item) {
                const index = Array.from(galleryGrid.children).indexOf(item);
                if (index > -1) {
                    showLightbox(index);
                }
            }
        });
    }

    // Functie om nieuwe items aan de DOM toe te voegen
    const appendImages = (count) => {
        if (!galleryGrid || loadedCount >= galleryImages.length) return;

        const limit = Math.min(loadedCount + count, galleryImages.length);
        const fragment = document.createDocumentFragment();

        for (let i = loadedCount; i < limit; i++) {
            const imagePath = galleryImages[i];

            // Haal bestandsnaam op en strip de prefix en extensie
            const fileName = imagePath.split('/').pop().replace('.webp', '');
            const title = fileName.length > 5 ? fileName.substring(5) : fileName; // Fallback als de naam kort is

            const div = document.createElement('div');
            div.className = 'gallery-item gallery-clickable';
            div.dataset.full = imagePath;
            div.innerHTML = `
                <img src="${imagePath}" alt="${title}" loading="lazy">
                <div class="gallery-item-title" style="white-space: normal; line-height: 1.2; padding: 5px;">
                    ${title}
                </div>
            `;

            fragment.appendChild(div);
        }

        galleryGrid.appendChild(fragment);
        loadedCount = limit;

        // Check if the sentinel is still visible, if so, load more
        // This fixes the issue on large screens where one batch isn't enough to push the sentinel down
        if (observer && scrollSentinel && loadedCount < galleryImages.length) {
            setTimeout(() => {
                const rect = scrollSentinel.getBoundingClientRect();
                if (rect.top <= window.innerHeight + 400) {
                    appendImages(batchSize);
                }
            }, 50);
        } else if (loadedCount >= galleryImages.length && observer && scrollSentinel) {
            observer.unobserve(scrollSentinel);
        }
    };

    // --- INFINITE SCROLL OBSERVER ---
    let observer;
    const initObserver = () => {
        if (!galleryGrid || !scrollSentinel || loadedCount >= galleryImages.length) return;

        observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                appendImages(batchSize);
            }
        }, { rootMargin: "400px" }); // Laad iets eerder dan het scherm bereikt

        observer.observe(scrollSentinel);

        // Initial check in case the screen is so large the sentinel is immediately visible
        // even before user scrolls, but the observer didn't catch the initial state change.
        setTimeout(() => {
             const rect = scrollSentinel.getBoundingClientRect();
             if (rect.top <= window.innerHeight + 400 && loadedCount < galleryImages.length) {
                 appendImages(batchSize);
             }
        }, 100);
    };

    // --- LIGHTBOX LOGICA ---
    const lightboxOverlay = document.querySelector('.lightbox-overlay');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxClose = document.querySelector('.lightbox-close');
    const prevButton = document.querySelector('.lightbox-prev');
    const nextButton = document.querySelector('.lightbox-next');

    let currentIndex = 0;

    const showLightbox = (index) => {
        const items = galleryGrid ? Array.from(galleryGrid.children) : [];
        if (!items[index]) return;

        const item = items[index];
        const fullSrc = item.dataset.full;
        let titleEl = item.querySelector('.gallery-item-title') || item.querySelector('.cover-item-title');
        const title = titleEl ? titleEl.textContent : item.dataset.full;

        if(lightboxImg) lightboxImg.src = fullSrc;
        if(lightboxImg) lightboxImg.alt = title;
        if(lightboxTitle) lightboxTitle.textContent = title;
        if(lightboxOverlay) lightboxOverlay.style.display = 'flex';
        currentIndex = index;
    };

    const showNextImage = () => {
        const items = galleryGrid ? Array.from(galleryGrid.children) : [];
        if(items.length === 0) return;
        const nextIndex = (currentIndex + 1) % items.length;
        showLightbox(nextIndex);
    };

    const showPrevImage = () => {
        const items = galleryGrid ? Array.from(galleryGrid.children) : [];
        if(items.length === 0) return;
        const prevIndex = (currentIndex - 1 + items.length) % items.length;
        showLightbox(prevIndex);
    };

    const closeLightbox = () => {
        if(lightboxOverlay) lightboxOverlay.style.display = 'none';
        if(lightboxImg) lightboxImg.src = '';
    };

    if (nextButton) {
        nextButton.addEventListener('click', (e) => {
            e.stopPropagation();
            showNextImage();
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', (e) => {
            e.stopPropagation();
            showPrevImage();
        });
    }

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', (e) => {
            if (e.target === lightboxOverlay) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (lightboxOverlay && lightboxOverlay.style.display === 'flex') {
            if (e.key === "Escape") closeLightbox();
            if (e.key === "ArrowRight") showNextImage();
            if (e.key === "ArrowLeft") showPrevImage();
        }
    });

    // Touch support voor mobiel (swipen)
    let touchStartX = 0;
    let touchEndX = 0;

    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, {passive: true});

        lightboxOverlay.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, {passive: true});
    }

    function handleSwipe() {
        const swipeThreshold = 50;
        if (touchEndX < touchStartX - swipeThreshold) showNextImage();
        if (touchEndX > touchStartX + swipeThreshold) showPrevImage();
    }

    // Initialize gallery
    initObserver();
});