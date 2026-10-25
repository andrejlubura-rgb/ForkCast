/*
 * ForkCast navbar helpers.
 * Adds the compact state while scrolling, opens the admin menu on hover and
 * closes the mobile menu after a navigation link changes the view. Routing
 * stays in router.js.
 */
(function () {
    'use strict';

    var header = document.getElementById('appHeader');
    var panel = document.getElementById('appNavbar');

    if (!header) {
        return;
    }

    var ticking = false;

    /*
     * The compact state makes the header shorter, which moves the page content
     * and can make the browser adjust the scroll position to keep that content
     * steady (scroll anchoring). With a single threshold, that adjustment could
     * push the scroll position back over the threshold and flip the state over
     * and over, which looked like the header shaking near the top of the page.
     * The two values below are farther apart than the header's height change,
     * so a single adjustment can no longer flip the state back.
     */
    var compactAt = 32;
    var expandAt = 4;

    function updateHeaderState() {
        var scrollPosition = window.scrollY;

        if (scrollPosition > compactAt) {
            header.classList.add('is-scrolled');
        } else if (scrollPosition < expandAt) {
            header.classList.remove('is-scrolled');
        }

        ticking = false;
    }

    function onScroll() {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(updateHeaderState);
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    updateHeaderState();

    /*
     * The admin menu opens on hover for mouse users. Bootstrap's click toggle
     * stays in place for keyboards and touch screens, which have no hover.
     * Bootstrap focuses the toggle when it opens a menu, so the hover path
     * gives focus back to avoid stealing it from whatever the user was doing.
     */
    var adminMenu = header.querySelector('.dropdown[data-role="admin"]');
    var adminToggle = adminMenu ? adminMenu.querySelector('[data-bs-toggle="dropdown"]') : null;

    if (adminToggle && window.bootstrap && window.bootstrap.Dropdown) {
        var hoverQuery = window.matchMedia('(min-width: 992px) and (hover: hover)');
        var hoverHideTimer = null;

        var getAdminDropdown = function () {
            return window.bootstrap.Dropdown.getOrCreateInstance(adminToggle);
        };

        var openAdminMenu = function () {
            var focusedElement = document.activeElement;

            window.clearTimeout(hoverHideTimer);

            if (!hoverQuery.matches || adminToggle.classList.contains('show')) {
                return;
            }

            getAdminDropdown().show();

            // Bootstrap focuses the toggle while opening it. On hover that
            // would leave the focus outline around the pill, so return focus
            // to where it was, or drop it if nothing was focused before.
            if (document.activeElement === adminToggle && focusedElement !== adminToggle) {
                if (focusedElement && focusedElement !== document.body) {
                    focusedElement.focus({ preventScroll: true });
                } else {
                    adminToggle.blur();
                }
            }
        };

        var closeAdminMenu = function () {
            window.clearTimeout(hoverHideTimer);

            if (!hoverQuery.matches) {
                return;
            }

            // The menu sits a little below the toggle, so give the pointer time
            // to cross that gap before the menu closes underneath it.
            hoverHideTimer = window.setTimeout(function () {
                getAdminDropdown().hide();
            }, 150);
        };

        adminMenu.addEventListener('mouseenter', openAdminMenu);
        adminMenu.addEventListener('mouseleave', closeAdminMenu);

        // A mouse click on the hover-open menu would toggle it shut again.
        // Keyboard clicks report detail 0 and still toggle as before.
        adminToggle.addEventListener('click', function (event) {
            if (hoverQuery.matches && event.detail > 0 && adminToggle.classList.contains('show')) {
                event.stopPropagation();
            }
        });
    }

    if (!panel) {
        return;
    }

    // The collapse does not close itself when a hash link changes the view.
    panel.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="#"]');

        if (!link || !panel.classList.contains('show')) {
            return;
        }

        if (window.matchMedia('(min-width: 992px)').matches) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).hide();
        }
    });
})();
