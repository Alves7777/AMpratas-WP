<?php
get_header();
?>

<div class="container-luxe pb-20 pt-8 lg:pt-12">
    <?php if (function_exists('is_cart') && is_cart()) : ?>
        <p class="am-kicker">Sacola</p>
        <h1 class="am-page-title">Sua seleção</h1>
    <?php elseif (function_exists('is_checkout') && is_checkout() && ! is_wc_endpoint_url('order-received')) : ?>
        <p class="am-kicker">Checkout</p>
        <h1 class="am-page-title">Finalizar pedido</h1>
    <?php elseif (function_exists('is_account_page') && is_account_page()) : ?>
        <p class="am-kicker">Conta</p>
        <h1 class="am-page-title"><?php echo esc_html(is_user_logged_in() ? 'Sua conta' : 'Entrar'); ?></h1>
    <?php elseif (is_shop() || is_product_category() || is_product_tag()) : ?>
        <p class="text-sm text-mute">Loja</p>
        <h1 class="section-title mt-2"><?php echo esc_html(is_product_taxonomy() ? single_term_title('', false) : 'Todas as joias'); ?></h1>
        <?php if (is_product_taxonomy() && term_description()) : ?>
            <div class="mt-3 max-w-xl leading-relaxed text-ink/70"><?php echo wp_kses_post(term_description()); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mt-8 lg:mt-10">
        <?php woocommerce_content(); ?>
    </div>
</div>

<?php
get_footer();
