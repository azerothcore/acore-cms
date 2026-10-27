/* ACore Theme: dark / light toggle and the "More" menu dropdown. */
(function () {
    'use strict';

    var html = document.documentElement;

    function setMode(mode) {
        html.classList.remove('acore-dark', 'acore-light');
        html.classList.add('acore-' + mode);
        try { localStorage.setItem('acore_theme', mode); } catch (e) {}
    }

    document.querySelectorAll('.acore-mode-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(html.classList.contains('acore-light') ? 'dark' : 'light');
        });
    });

    document.querySelectorAll('.acore-nav-more').forEach(function (more) {
        var button = more.querySelector('.acore-nav-more-btn');
        var menu = more.querySelector('.acore-nav-dropdown');
        if (!button || !menu) {
            return;
        }

        function setOpen(open) {
            menu.hidden = !open;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            setOpen(menu.hidden);
        });
        document.addEventListener('click', function (event) {
            if (!more.contains(event.target)) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !menu.hidden) {
                setOpen(false);
                button.focus();
            }
        });
    });
})();
