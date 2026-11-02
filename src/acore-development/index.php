<?php
/**
 * The only page template: pages, posts and lists of posts. WooCommerce pages use WooCommerce's own templates,
 * which call this theme's header.php and footer.php.
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('acore-entry'); ?>>
            <?php if (is_singular()) : ?>
                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                <div class="entry-content"><?php the_content(); ?></div>
            <?php else : ?>
                <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="entry-content"><?php the_excerpt(); ?></div>
            <?php endif; ?>
        </article>
        <?php
    endwhile;

    the_posts_pagination();
else :
    ?>
    <p><?php esc_html_e('Nothing found.', 'acore-development'); ?></p>
    <?php
endif;

get_footer();
