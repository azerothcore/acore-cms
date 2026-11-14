<?php
/**
 * The [acore_store] markup. Variables: $categories (top-level WP_Term[]), $products (WC_Product[]).
 */

defined('ABSPATH') || exit;

$hasCategories = !empty($categories);
?>
<?php if (function_exists('acore_theme_points_panel')) echo acore_theme_points_panel(); // escaped in the panel ?>
<?php if (function_exists('acore_theme_product_admin_toolbar')) echo acore_theme_product_admin_toolbar(); // escaped in the function ?>
<div class="acore-store" data-has-categories="<?php echo $hasCategories ? '1' : '0'; ?>">

    <?php if ($hasCategories) : ?>
    <div class="acore-store-categories">
        <?php foreach ($categories as $category) :
            $thumbId = (int) get_term_meta($category->term_id, 'thumbnail_id', true);
            $imgUrl  = $thumbId ? wp_get_attachment_image_url($thumbId, 'medium') : '';
        ?>
        <button type="button" class="acore-store-category" data-category="<?php echo esc_attr($category->slug); ?>" data-name="<?php echo esc_attr($category->name); ?>">
            <span class="acore-store-category-img">
                <?php if ($imgUrl) : ?>
                    <img src="<?php echo esc_url($imgUrl); ?>" alt="" loading="lazy">
                <?php else : ?>
                    <span class="acore-store-category-letter" aria-hidden="true"><?php echo esc_html(mb_strtoupper(mb_substr($category->name, 0, 1))); ?></span>
                <?php endif; ?>
            </span>
            <span class="acore-store-category-name"><?php echo esc_html($category->name); ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="acore-store-products"<?php if ($hasCategories) echo ' hidden'; ?>>
        <div class="acore-store-header">
            <?php if ($hasCategories) : ?>
                <button type="button" class="acore-store-back">&larr; <?php esc_html_e('Back', 'acore-development'); ?></button>
            <?php endif; ?>
            <h2 class="acore-store-title"><?php esc_html_e('All products', 'acore-development'); ?></h2>
            <input type="search" class="acore-store-search" placeholder="<?php esc_attr_e('Search...', 'acore-development'); ?>" aria-label="<?php esc_attr_e('Search products', 'acore-development'); ?>" autocomplete="off">
        </div>

        <ul class="acore-store-grid">
            <?php foreach ($products as $product) :
                $link   = $product->get_permalink();
                $imgId  = $product->get_image_id();
                $imgUrl = $imgId ? wp_get_attachment_image_url($imgId, 'medium') : wc_placeholder_img_src('medium');
                $imgClass = $imgId ? '' : ' class="acore-placeholder-logo"';
                $direct = !acore_theme_product_needs_choices($product) && $product->is_purchasable() && $product->is_in_stock();
            ?>
            <li class="acore-store-item" data-categories="<?php echo esc_attr(implode(' ', acore_theme_product_top_categories($product->get_id()))); ?>" data-title="<?php echo esc_attr(mb_strtolower($product->get_name())); ?>">
                <?php if (function_exists('acore_theme_product_edit_button')) echo acore_theme_product_edit_button($product->get_id()); // escaped in the function ?>
                <a href="<?php echo esc_url($link); ?>" class="acore-store-item-img">
                    <img src="<?php echo esc_url($imgUrl); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy"<?php echo $imgClass; ?>>
                </a>
                <div class="acore-store-item-body">
                    <h3 class="acore-store-item-title"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
                    <span class="acore-store-price"><?php echo wp_kses_post(acore_theme_store_price_html($product)); ?></span>
                    <div class="acore-store-actions">
                        <a href="<?php echo esc_url($link); ?>" class="acore-store-btn acore-store-btn-secondary">
                            <?php $direct ? esc_html_e('Details', 'acore-development') : esc_html_e('Choose', 'acore-development'); ?>
                        </a>
                        <?php if ($direct) : ?>
                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="acore-store-btn acore-store-btn-primary" rel="nofollow"><?php esc_html_e('Add to cart', 'acore-development'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <p class="acore-store-empty"<?php if (!empty($products)) echo ' hidden'; ?>><?php esc_html_e('No products found.', 'acore-development'); ?></p>
    </div>
</div>
