/*
 * Footer "back to top" button.
 * Scrolls the window back to the top smoothly. It is a button rather than a
 * #home link, so the current view is not reloaded and only the scroll position
 * changes. Users who prefer reduced motion get an instant jump instead.
 */
(function () {
    'use strict';

    var button = document.getElementById('siteBackToTop');

    if (!button) {
        return;
    }

    button.addEventListener('click', function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.scrollTo({
            top: 0,
            left: 0,
            behavior: reduceMotion ? 'auto' : 'smooth'
        });
    });
})();
