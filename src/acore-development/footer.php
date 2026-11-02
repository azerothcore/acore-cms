<?php
/**
 * Page footer.
 */

defined('ABSPATH') || exit;
?>
</main>

<footer class="acore-footer">
    <div class="acore-container">
        &copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
