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
    var isCompact = false;

    /*
     * The compact state only moves the header with a CSS transform, so it does
     * not change the page layout and cannot trigger scroll anchoring. The two
     * thresholds are still kept apart so a trackpad resting near the boundary
     * does not make the header flip back and forth.
     *
     * expandAt is larger than the distance the compact bar slides up (0.7rem
     * at the default font size), so the header starts expanding while the
     * content below it is still under the bar. That avoids showing a strip of
     * page background near the top.
     */
    var compactAt = 32;
    var expandAt = 16;

    function updateHeaderState() {
        var scrollPosition = window.scrollY;
        var shouldBeCompact = isCompact ? scrollPosition > expandAt : scrollPosition > compactAt;

        // Only touch the DOM when the state actually changes, not on every frame.
        if (shouldBeCompact !== isCompact) {
            isCompact = shouldBeCompact;
            header.classList.toggle('is-scrolled', isCompact);
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
