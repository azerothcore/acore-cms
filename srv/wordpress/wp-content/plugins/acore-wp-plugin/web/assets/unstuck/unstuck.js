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
