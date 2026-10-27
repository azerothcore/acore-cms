/* Points panel: the Add buttons update the balance in place (no page reload). */
(function () {
    'use strict';

    document.querySelectorAll('.acore-points').forEach(function (panel) {
        var url = panel.getAttribute('data-ajax-url');
        var message = panel.querySelector('.acore-points-message');
        var amount = panel.querySelector('.acore-points-amount');
        if (!url) {
            return;
        }

        function show(text, ok) {
            if (!message) {
                return;
            }
            message.textContent = text;
            message.classList.toggle('acore-points-ok', ok);
            message.classList.toggle('acore-points-error', !ok);
            message.hidden = false;
        }

        panel.querySelectorAll('form.acore-points-buy').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var data = new FormData(form);
                // the pressed pack button carries the amount
                if (event.submitter && event.submitter.name) {
                    data.set(event.submitter.name, event.submitter.value);
                }
                data.set('action', 'acore_points_add');

                var buttons = form.querySelectorAll('button');
                buttons.forEach(function (button) { button.disabled = true; });

                fetch(url, { method: 'POST', body: data, credentials: 'same-origin' })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        var info = result && result.data ? result.data : {};
                        show(info.message || '', !!(result && result.success));
                        if (info.balance) {
                            if (amount) {
                                amount.textContent = info.balance;
                            }
                            document.querySelectorAll('.acore-account-points').forEach(function (badge) {
                                badge.textContent = info.balance;
                            });
                        }
                        if (result && result.success) {
                            var custom = form.querySelector('input[type="number"]');
                            if (custom) {
                                custom.value = '';
                            }
                        }
                    })
                    .catch(function () {
                        show('The points could not be added.', false);
                    })
                    .then(function () {
                        buttons.forEach(function (button) { button.disabled = false; });
                    });
            });
        });
    });
})();
