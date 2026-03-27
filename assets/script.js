document.addEventListener('DOMContentLoaded', () => {
    // --- SELECTOREN ---
    // Zoek alle items die klikbaar moeten zijn in de galerij.
    const clickableItems = Array.from(document.querySelectorAll('.gallery-clickable, .gallery-item'));
    
    // Stop als er geen afbeeldingen zijn om te tonen.
    if (clickableItems.length === 0) {
        return;
    }

    const lightboxOverlay = document.querySelector('.lightbox-overlay');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxClose = document.querySelector('.lightbox-close');
    const prevButton = document.querySelector('.lightbox-prev');
    const nextButton = document.querySelector('.lightbox-next');

    let currentIndex = 0;

    // --- FUNCTIES ---

    // Functie om de lightbox te tonen met de juiste afbeelding
    const showLightbox = (index) => {
        const item = clickableItems[index];
        const fullSrc = item.dataset.full;
        let titleEl = item.querySelector('.gallery-item-title') || item.querySelector('.cover-item-title');
        const title = titleEl ? titleEl.textContent : item.dataset.full;

        lightboxImg.src = fullSrc;
        lightboxImg.alt = title;
        lightboxTitle.textContent = title;
        lightboxOverlay.style.display = 'flex';
        currentIndex = index; // Update de huidige index
    };

    // Functie om naar de volgende afbeelding te gaan
    const showNextImage = () => {
        // De modulo (%) zorgt ervoor dat de index na de laatste foto weer naar 0 springt.
        const nextIndex = (currentIndex + 1) % clickableItems.length;
        showLightbox(nextIndex);
    };

    // Functie om naar de vorige afbeelding te gaan
    const showPrevImage = () => {
        // Deze formule zorgt ervoor dat de index vanaf 0 naar de laatste foto springt.
        const prevIndex = (currentIndex - 1 + clickableItems.length) % clickableItems.length;
        showLightbox(prevIndex);
    };

    // Functie om de lightbox te sluiten
    const closeLightbox = () => {
        lightboxOverlay.style.display = 'none';
    };


    // --- EVENT LISTENERS ---

    // 1. Voeg een click event toe aan elke afbeelding in de galerij
    clickableItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            showLightbox(index);
        });
    });

    // 2. Koppel de functies aan de pijltjesknoppen
    nextButton.addEventListener('click', (e) => {
        e.stopPropagation(); // Voorkom dat de overlay sluit
        showNextImage();
    });

    prevButton.addEventListener('click', (e) => {
        e.stopPropagation(); // Voorkom dat de overlay sluit
        showPrevImage();
    });

    // 3. Koppel de sluitfunctie aan de knoppen en de achtergrond
    lightboxClose.addEventListener('click', closeLightbox);
    
    lightboxOverlay.addEventListener('click', (e) => {
        // Sluit alleen als er op de donkere achtergrond wordt geklikt, niet op de content
        if (e.target === lightboxOverlay) {
            closeLightbox();
        }
    });

    // 4. Voeg toetsenbordbediening toe (Escape en pijltjestoetsen)
    document.addEventListener('keydown', (e) => {
        // Voer alleen acties uit als de lightbox zichtbaar is
        if (lightboxOverlay.style.display === 'flex') {
            if (e.key === "Escape") {
                closeLightbox();
            }
            if (e.key === "ArrowRight") {
                showNextImage();
            }
            if (e.key === "ArrowLeft") {
                showPrevImage();
            }
        }
    });
});
