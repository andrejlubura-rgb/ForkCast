/*
 * ForkCast hash router.
 * Loads each view fragment once and only toggles visibility afterwards.
 */
(function ($) {
    'use strict';

    // Known route names and the titles shown in the browser tab.
    var routes = {
        home: 'Home',
        login: 'Login',
        register: 'Register',
        recipes: 'Recipes',
        recipe: 'Recipe',
        'recipe-form': 'Recipe form',
        discover: 'Discover',
        planner: 'Planner',
        profile: 'Profile',
        'admin-categories': 'Categories',
        'admin-users': 'Users',
        404: 'Page not found'
    };

    // View names that have already been fetched and appended to #app.
    var loadedViews = {};

    // Reads the hash, defaults to home and sends unknown hashes to the 404 view.
    function getRouteName() {
        var hash = window.location.hash.slice(1);

        if (hash === '') {
            return 'home';
        }

        return Object.prototype.hasOwnProperty.call(routes, hash) ? hash : '404';
    }

    // Builds the relative path of the HTML fragment for a view.
    function getViewUrl(name) {
        return 'views/' + name + '.html';
    }

    // Fetches a view fragment once, appends it to #app and shows it.
    function loadView(name) {
        $.get(getViewUrl(name), function (html) {
            loadedViews[name] = true;

            var $view = $('<section>', {
                'id': 'view-' + name,
                'class': 'view d-none'
            }).html(html);

            $('#app').append($view);
            $view.removeClass('d-none');

            // Lets view helpers (for example js/home.js) decorate the new markup.
            $(document).trigger('view:loaded', [name]);
        });
    }

    // Shows a view and loads it only the first time it is visited.
    function renderView(name) {
        $('.view').addClass('d-none');

        if (loadedViews[name]) {
            $('#view-' + name).removeClass('d-none');
        } else {
            loadView(name);
        }
    }

    // Highlights the navbar link that matches the current route.
    function updateNav(name) {
        $('.navbar .nav-link, .navbar .dropdown-item').removeClass('active').removeAttr('aria-current');
        $('.navbar .nav-link[href="#' + name + '"], .navbar .dropdown-item[href="#' + name + '"]')
            .addClass('active')
            .attr('aria-current', 'page');
    }

    // Updates the browser tab title for the current route.
    function updateTitle(name) {
        document.title = 'ForkCast | ' + routes[name];
    }

    // Shows the view for the current hash and updates the nav and the title.
    function navigate() {
        var name = getRouteName();

        renderView(name);
        updateNav(name);
        updateTitle(name);
    }

    // Starts the router and runs it again whenever the hash changes.
    function init() {
        $(window).on('hashchange', navigate);
        navigate();
    }

    window.ForkCastRouter = { init: init };
})(jQuery);
