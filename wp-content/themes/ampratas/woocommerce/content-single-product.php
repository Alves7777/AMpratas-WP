<?php

defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
    <?php woocommerce_breadcrumb(); ?>
    <div class="am-product-layout">
        <div class="am-product-media">
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </div>
        <div class="summary entry-summary">
            <?php do_action('woocommerce_single_product_summary'); ?>
        </div>
    </div>
    <div class="am-product-after">
        <?php do_action('woocommerce_after_single_product_summary'); ?>
    </div>
</div>
<?php
do_action('woocommerce_after_single_product');
