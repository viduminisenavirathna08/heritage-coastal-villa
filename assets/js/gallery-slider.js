/**
 * HERITAGE COASTAL VILLA - MULTI-IMAGE CAROUSEL & LIGHTBOX SLIDER (Vanilla JS)
 * Allows tourists to swap/slide through multiple photos on each card,
 * and click to view in an interactive full-screen photo gallery modal.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. INLINE CARD CAROUSEL (Swap photos on the card directly)
    const carousels = document.querySelectorAll('.card-carousel');

    carousels.forEach(function (carousel) {
        const imgElement = carousel.querySelector('.carousel-img');
        const prevBtn = carousel.querySelector('.carousel-btn.prev');
        const nextBtn = carousel.querySelector('.carousel-btn.next');
        const dotsContainer = carousel.querySelector('.carousel-dots');
        const viewAllBtn = carousel.querySelector('.view-gallery-badge');

        // Parse image array from data attribute
        let images = [];
        try {
            images = JSON.parse(carousel.getAttribute('data-images') || '[]');
        } catch (e) {
            images = [imgElement.src];
        }

        if (!images || images.length <= 1) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            return;
        }

        let currentIndex = 0;

        // Generate Dots
        if (dotsContainer) {
            dotsContainer.innerHTML = '';
            images.forEach((_, idx) => {
                const dot = document.createElement('span');
                dot.classList.add('carousel-dot');
                if (idx === 0) dot.classList.add('active');
                dot.addEventListener('click', (e) => {
                    e.stopPropagation();
                    goToSlide(idx);
                });
                dotsContainer.appendChild(dot);
            });
        }

        function updateDots() {
            if (!dotsContainer) return;
            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        }

        function goToSlide(index) {
            currentIndex = (index + images.length) % images.length;
            imgElement.style.opacity = '0.4';
            setTimeout(() => {
                imgElement.src = images[currentIndex];
                imgElement.style.opacity = '1';
                updateDots();
            }, 120);
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                goToSlide(currentIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                goToSlide(currentIndex + 1);
            });
        }

        // Open Lightbox when clicking photo or badge
        function openItemLightbox(e) {
            e.preventDefault();
            const title = carousel.getAttribute('data-title') || 'Experience Gallery';
            openLightboxModal(images, currentIndex, title);
        }

        imgElement.addEventListener('click', openItemLightbox);
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', openItemLightbox);
        }

        // Touch Swipe Support for Mobile
        let touchStartX = 0;
        let touchEndX = 0;
        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        carousel.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 45) {
                goToSlide(currentIndex + 1); // Swipe Left -> Next
            } else if (touchEndX - touchStartX > 45) {
                goToSlide(currentIndex - 1); // Swipe Right -> Prev
            }
        }, { passive: true });
    });

    // 2. FULLSCREEN LIGHTBOX MODAL LOGIC
    const lightboxModal = document.getElementById('lightbox_modal');
    const lightboxImg = document.getElementById('lightbox_img');
    const lightboxTitle = document.getElementById('lightbox_title');
    const lightboxCounter = document.getElementById('lightbox_counter');
    const lightboxPrev = document.getElementById('lightbox_prev');
    const lightboxNext = document.getElementById('lightbox_next');
    const lightboxClose = document.getElementById('lightbox_close');

    let activeGallery = [];
    let activeIndex = 0;

    function openLightboxModal(images, startIndex, title) {
        if (!lightboxModal) return;
        activeGallery = images;
        activeIndex = startIndex || 0;
        if (lightboxTitle) lightboxTitle.textContent = title;
        updateLightboxContent();
        lightboxModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // prevent scrolling
    }

    function updateLightboxContent() {
        if (!lightboxImg || activeGallery.length === 0) return;
        lightboxImg.src = activeGallery[activeIndex];
        if (lightboxCounter) {
            lightboxCounter.textContent = `${activeIndex + 1} / ${activeGallery.length}`;
        }
    }

    function closeLightbox() {
        if (!lightboxModal) return;
        lightboxModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxModal) {
        lightboxModal.addEventListener('click', function (e) {
            if (e.target === lightboxModal) closeLightbox();
        });
    }

    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', function () {
            activeIndex = (activeIndex - 1 + activeGallery.length) % activeGallery.length;
            updateLightboxContent();
        });
    }

    if (lightboxNext) {
        lightboxNext.addEventListener('click', function () {
            activeIndex = (activeIndex + 1) % activeGallery.length;
            updateLightboxContent();
        });
    }

    // Keyboard Arrow Keys support
    document.addEventListener('keydown', function (e) {
        if (!lightboxModal || !lightboxModal.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft' && lightboxPrev) lightboxPrev.click();
        if (e.key === 'ArrowRight' && lightboxNext) lightboxNext.click();
    });
});
