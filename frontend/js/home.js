/*
 * ForkCast home view helpers.
 * Repeats the "Recently added" cards so the row can scroll in a seamless loop:
 * every copy is identical and the CSS animation moves the track by exactly one
 * group width, which lands on the next copy. Enough copies are added to keep
 * the row filled on wide screens. With reduced motion the row stays still.
 * It also pauses the home animations while their section is off screen.
 */
(function ($) {
    'use strict';

    var resizeTimer = null;

    // Copies are decoration only: hidden from assistive technology and taken
    // out of the tab order so nobody tabs through the same cards twice.
    function createGroupCopy($group) {
        var $copy = $group
            .clone()
            .addClass('home-marquee-group-copy')
            .attr('aria-hidden', 'true');

        $copy.find('a, button').attr('tabindex', '-1');

        return $copy;
    }

    // Makes the card track long enough to loop and starts the animation.
    function updateRecentLoop() {
        var $marquee = $('#home-recently-marquee');
        var $track = $marquee.find('.home-marquee-track');
        var $group = $track.children('.home-marquee-group').first();
        var groupWidth;
        var marqueeWidth;
        var shift;

        if ($group.length === 0) {
            return;
        }

        // Drop the copies from the previous run first. Without this, every
        // resize that widens the window left the old copies in place and the
        // track (and its composited layer) kept growing.
        $track.children('.home-marquee-group-copy').remove();

        // Phones swipe the row instead of watching it move, and reduced motion
        // keeps it still, so neither one needs the extra copies.
        if (window.matchMedia('(prefers-reduced-motion: reduce), (max-width: 767.98px)').matches) {
            return;
        }

        groupWidth = $group[0].getBoundingClientRect().width;
        marqueeWidth = $marquee[0].getBoundingClientRect().width;

        if (groupWidth === 0 || marqueeWidth === 0) {
            return;
        }

        // While the track slides, a full screen of cards has to stay to the right
        // of the visible ones, otherwise a gap opens at the end of the loop.
        while (($track.children('.home-marquee-group').length - 1) * groupWidth < marqueeWidth) {
            $track.append(createGroupCopy($group));
        }

        // Moving by exactly one group width lands on an identical copy.
        shift = '-' + groupWidth + 'px';

        if ($marquee[0].style.getPropertyValue('--home-marquee-shift') === shift) {
            return;
        }

        $marquee.removeClass('home-marquee-running');
        $marquee[0].style.setProperty('--home-marquee-shift', shift);
        // Applies the new distance before the animation restarts from the start.
        void $marquee[0].offsetWidth;
        $marquee.addClass('home-marquee-running');
    }

    var motionSections = ['home-hero', 'home-how-it-works', 'home-recently-added'];
    var motionObserver = null;

    /*
     * The hero cross-fade, the card line and the card row animate all the time,
     * even after their section scrolled out of view. This observer marks the
     * hidden sections, and the CSS pauses their animations, so the browser only
     * works on what the user can actually see.
     */
    function pauseHiddenSections() {
        if (!('IntersectionObserver' in window)) {
            return;
        }

        if (!motionObserver) {
            motionObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    entry.target.classList.toggle('is-motion-paused', !entry.isIntersecting);
                });
            });
        }

        motionSections.forEach(function (id) {
            var section = document.getElementById(id);

            if (section) {
                motionObserver.observe(section);
            }
        });
    }

    $(document).on('view:loaded', function (event, viewName) {
        if (viewName === 'home') {
            updateRecentLoop();
            pauseHiddenSections();
        }
    });

    // Card widths follow the viewport, so the loop is rebuilt after a resize.
    $(window).on('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(updateRecentLoop, 200);
    });
})(jQuery);
