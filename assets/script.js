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
    let clickableItems = [];
    let galleryImages = window.galleryImages || [];
    let loadedCount = window.initialImageCount || 0;
    const batchSize = 20;

    // Functie om nieuwe items aan de DOM toe te voegen
    const appendImages = (count) => {
        if (!galleryGrid || loadedCount >= galleryImages.length) return;

        const limit = Math.min(loadedCount + count, galleryImages.length);
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

            // Voeg click event toe voor de nieuwe afbeelding
            div.addEventListener('click', () => {
                showLightbox(clickableItems.indexOf(div));
            });

            galleryGrid.appendChild(div);
            clickableItems.push(div);
        }
        loadedCount = limit;

        // Verplaats de observer naar het nieuwe laatste element
        observeLastItem();
    };

    // --- INFINITE SCROLL OBSERVER ---
    let observer;
    const observeLastItem = () => {
        if (!galleryGrid || loadedCount >= galleryImages.length) return;

        if (observer) {
            observer.disconnect();
        }

        observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                appendImages(batchSize);
            }
        }, { rootMargin: "200px" }); // Laad iets eerder dan het scherm bereikt

        const lastItem = galleryGrid.lastElementChild;
        if (lastItem) {
            observer.observe(lastItem);
        }
    };

    // --- LIGHTBOX LOGICA ---
    const lightboxOverlay = document.querySelector('.lightbox-overlay');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxClose = document.querySelector('.lightbox-close');
    const prevButton = document.querySelector('.lightbox-prev');
    const nextButton = document.querySelector('.lightbox-next');

    let currentIndex = 0;

    // Haal initiele items op (de eerste batch die al geladen is in de HTML)
    const initGalleryItems = () => {
        if (!galleryGrid) return;
        clickableItems = Array.from(galleryGrid.querySelectorAll('.gallery-clickable, .gallery-item'));
        clickableItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                showLightbox(index);
            });
        });
        observeLastItem();
    };

    const showLightbox = (index) => {
        if (!clickableItems[index]) return;
        const item = clickableItems[index];
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
        if(clickableItems.length === 0) return;
        const nextIndex = (currentIndex + 1) % clickableItems.length;
        showLightbox(nextIndex);
    };

    const showPrevImage = () => {
        if(clickableItems.length === 0) return;
        const prevIndex = (currentIndex - 1 + clickableItems.length) % clickableItems.length;
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
    initGalleryItems();
});