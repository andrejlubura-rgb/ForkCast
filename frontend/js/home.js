/*
 * ForkCast home view helpers.
 * Repeats the "Recently added" cards so the row can scroll in a seamless loop:
 * every copy is identical and the CSS animation moves the track by exactly one
 * group width, which lands on the next copy. Enough copies are added to keep
 * the row filled on wide screens. With reduced motion the row stays still.
 * It also pauses the home animations while their section is off screen, and
 * keeps the page still while an FAQ answer opens or closes.
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

    /*
     * The FAQ is the last section of the page, so when an answer opens there is
     * nothing below it that could stay still: the browser still tries to keep
     * the content under the answer in place and scrolls the page instead, which
     * looks like the content above the answer moving away. The scroll position
     * is therefore pinned for the length of the collapse animation, so the
     * answer pushes the footer down and the question stays where it was
     * clicked. A real scroll gesture (wheel, touch, key) releases the pin at
     * once, so the page never fights the user.
     */
    var FAQ_HOLD_MS = 500;
    var faqHold = {
        accordion: null,
        scrollTop: null,
        frame: null,
        timer: null
    };

    function keepFaqScrollTop() {
        var maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);

        // Closing an answer makes the page shorter than the pinned position, so
        // the browser already had to scroll up and there is nothing left to hold.
        if (faqHold.scrollTop > maxScroll) {
            faqHold.scrollTop = maxScroll;
        }

        if (window.scrollY !== faqHold.scrollTop) {
            window.scrollTo(0, faqHold.scrollTop);
        }

        faqHold.frame = window.requestAnimationFrame(keepFaqScrollTop);
    }

    function startFaqHold() {
        // The first event already records the position: the answer starts
        // changing the height right after it, and later events arrive during
        // the animation, when the page may already have moved.
        if (faqHold.scrollTop === null) {
            faqHold.scrollTop = window.scrollY;
            faqHold.frame = window.requestAnimationFrame(keepFaqScrollTop);
        }

        window.clearTimeout(faqHold.timer);
        faqHold.timer = window.setTimeout(stopFaqHold, FAQ_HOLD_MS);
    }

    function stopFaqHold() {
        window.clearTimeout(faqHold.timer);

        if (faqHold.frame !== null) {
            window.cancelAnimationFrame(faqHold.frame);
        }

        faqHold.frame = null;
        faqHold.scrollTop = null;
    }

    function holdFaqScrollTop() {
        var accordion = document.getElementById('home-faq-accordion');

        // The view is loaded once, so the listeners are added once.
        if (!accordion || faqHold.accordion) {
            return;
        }

        faqHold.accordion = accordion;

        // Pressing the question is the last moment before Bootstrap measures
        // and animates the answer.
        accordion.addEventListener('pointerdown', startFaqHold);
        accordion.addEventListener('show.bs.collapse', startFaqHold);
        accordion.addEventListener('hide.bs.collapse', startFaqHold);

        document.addEventListener('wheel', stopFaqHold, { passive: true });
        document.addEventListener('touchmove', stopFaqHold, { passive: true });
        document.addEventListener('keydown', stopFaqHold);
    }

    $(document).on('view:loaded', function (event, viewName) {
        if (viewName === 'home') {
            updateRecentLoop();
            pauseHiddenSections();
            holdFaqScrollTop();
        }
    });

    // Card widths follow the viewport, so the loop is rebuilt after a resize.
    $(window).on('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(updateRecentLoop, 200);
    });
})(jQuery);
