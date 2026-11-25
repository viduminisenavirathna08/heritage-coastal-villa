/**
 * HERITAGE COASTAL VILLA - INTERACTIVE PACKING GUIDE (JavaScript)
 * Interactive modal for tropical coastal Sri Lanka weather and excursions.
 */
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('open_packing_guide');
    const closeBtn = document.getElementById('close_packing_modal');
    const thankYouBtn = document.getElementById('thank_you_close_btn');
    const modal = document.getElementById('packing_guide_modal');

    if (!modal) return;

    function closeModal() {
        modal.classList.remove('active');
    }

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            modal.classList.add('active');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (thankYouBtn) {
        thankYouBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
