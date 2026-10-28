// Petites améliorations. Le site fonctionne aussi sans JavaScript.

// Demande confirmation avant une action sensible : <form data-confirm="Sûr ?">
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Les messages de succès disparaissent tout seuls après quelques secondes
document.querySelectorAll('.alert-success').forEach(function (alertBox) {
    setTimeout(function () { alertBox.remove(); }, 7000);
});

// Galeries de photos : compteur "2 / 5" + boutons ‹ › (affichés avec une souris).
// Sans JavaScript, le balayage horizontal fonctionne quand même.
document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
    var track = gallery.querySelector('.gallery-track');
    var slides = gallery.querySelectorAll('.gallery-slide');
    if (!track || slides.length < 2) { return; }

    var prev = gallery.querySelector('.gallery-prev');
    var next = gallery.querySelector('.gallery-next');
    var counter = gallery.querySelector('.gallery-count');

    function currentIndex() {
        var index = Math.round(track.scrollLeft / track.clientWidth);
        return Math.min(Math.max(index, 0), slides.length - 1);
    }

    function update() {
        var index = currentIndex();
        if (counter) { counter.textContent = (index + 1) + ' / ' + slides.length; }
        if (prev) { prev.disabled = index === 0; }
        if (next) { next.disabled = index === slides.length - 1; }
    }

    function go(step) {
        track.scrollBy({ left: step * track.clientWidth, behavior: 'smooth' });
    }

    if (prev) { prev.hidden = false; prev.addEventListener('click', function () { go(-1); }); }
    if (next) { next.hidden = false; next.addEventListener('click', function () { go(1); }); }

    track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    update();
});
