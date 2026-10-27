/* [acore_store]: category tiles, back button and search. */
(function () {
    'use strict';

    document.querySelectorAll('.acore-store').forEach(function (store) {
        var tiles = store.querySelector('.acore-store-categories');
        var view = store.querySelector('.acore-store-products');
        var back = store.querySelector('.acore-store-back');
        var title = store.querySelector('.acore-store-title');
        var search = store.querySelector('.acore-store-search');
        var empty = store.querySelector('.acore-store-empty');
        var items = store.querySelectorAll('.acore-store-item');
        var allTitle = title ? title.textContent : '';
        var category = '';

        function filter() {
            var text = search ? search.value.trim().toLowerCase() : '';
            var shown = 0;
            items.forEach(function (item) {
                var categories = (item.getAttribute('data-categories') || '').split(' ');
                var match = (category === '' || categories.indexOf(category) !== -1)
                    && (text === '' || (item.getAttribute('data-title') || '').indexOf(text) !== -1);
                item.hidden = !match;
                if (match) {
                    shown++;
                }
            });
            if (empty) {
                empty.hidden = shown > 0;
            }
        }

        function showCategory(slug, name) {
            category = slug;
            if (title) {
                title.textContent = name;
            }
            if (tiles) {
                tiles.hidden = true;
            }
            view.hidden = false;
            filter();
            if (search) {
                search.focus();
            }
        }

        function showTiles() {
            category = '';
            if (search) {
                search.value = '';
            }
            if (title) {
                title.textContent = allTitle;
            }
            view.hidden = true;
            if (tiles) {
                tiles.hidden = false;
            }
        }

        store.querySelectorAll('.acore-store-category').forEach(function (tile) {
            tile.addEventListener('click', function () {
                showCategory(tile.getAttribute('data-category'), tile.getAttribute('data-name'));
            });
        });
        if (back) {
            back.addEventListener('click', showTiles);
        }
        if (search) {
            search.addEventListener('input', filter);
        }
    });
})();
