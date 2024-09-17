jQuery(document).ready(function() {
    jQuery(".unstuck-button").on("click", function() {
        const charName = jQuery(this).data("char-name");

        jQuery.ajax({
            type: "POST",
            url: unstuckData.restUrl, // Use the passed REST URL
            data: { charName: charName },
            headers: { "X-WP-Nonce": unstuckData.nonce }, // Use the nonce
            xhrFields: { withCredentials: true }, // Ensure cookies are sent
            success: function(response) {
                console.log(response);
                location.reload(); // Reload after success
            },
            error: function(xhr, status, error) {
                console.error("Unstuck failed:", error);
            }
        });
    });
});


document.addEventListener('DOMContentLoaded', function() {
    function updateCountdown() {
        const now = Math.floor(Date.now() / 1000); // Get current time in seconds

        document.querySelectorAll('.countdown').forEach(function(span) {
            const endTime = parseInt(span.getAttribute('data-end-time'));
            const remainingTime = endTime - now;

            if (remainingTime <= 0) {
                span.textContent = ''; // Clear text when countdown ends

                // Re-enable button
                const container = span.closest('.menu-item-handle');
                const button = container.querySelector('.unstuck-button');
                if (button) {
                }
                button.removeAttribute('disabled');
                clearInterval(remainingTime)
            } else {
                // Format remaining time as H:i:s
                const hours = Math.floor(remainingTime / 3600);
                const minutes = Math.floor((remainingTime % 3600) / 60);
                const seconds = remainingTime % 60;
                span.textContent = [hours, minutes, seconds].map(unit => String(unit).padStart(2, '0')).join(':');
            }
        });
    }

    // Update countdown every second
    setInterval(updateCountdown, 1000);
    updateCountdown();
});
