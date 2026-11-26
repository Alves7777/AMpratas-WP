<?php

defined('ABSPATH') || exit;

global $product;

if (! $product instanceof WC_Product || ! $product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('', $product); ?>>
    <?php echo ampratas_product_card_html($product); ?>
</li>
