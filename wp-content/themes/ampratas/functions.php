<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require_once get_template_directory().'/inc/mail.php';
require_once get_template_directory().'/inc/mercadopago-test.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => 'Menu principal',
    ]);
});

add_action('wp_head', static function (): void {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">'."\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'."\n";
}, 1);

add_action('wp_enqueue_scripts', static function (): void {
    // As fontes (Newsreader e Public Sans) entram pelo @import do próprio app.css, igual à vitrine Laravel.
    $css = get_template_directory().'/assets/css/app.css';
    $css_deps = [];
    if (wp_style_is('wc-blocks-style', 'registered')) {
        $css_deps[] = 'wc-blocks-style';
    }
    wp_enqueue_style(
        'ampratas-app',
        get_template_directory_uri().'/assets/css/app.css',
        $css_deps,
        file_exists($css) ? (string) filemtime($css) : '2.0.0'
    );

    $js = get_template_directory().'/assets/js/theme.js';
    wp_enqueue_script(
        'ampratas-theme',
        get_template_directory_uri().'/assets/js/theme.js',
        [],
        file_exists($js) ? (string) filemtime($js) : '2.0.0',
        true
    );

    if (class_exists('WooCommerce')) {
        wp_enqueue_script('wc-cart-fragments');
    }
});

add_filter('woocommerce_enqueue_styles', '__return_empty_array');

add_filter('woocommerce_add_to_cart_fragments', static function (array $fragments): array {
    $fragments['.js-cart-count'] = ampratas_cart_count_html();

    return $fragments;
});

/**
 * @return array<int, array{name:string,slug:string,image:string}>
 */
function ampratas_category_cards(): array
{
    return [
        ['name' => 'Alianças', 'slug' => 'aliancas', 'image' => 'cat-aliancas.jpg'],
        ['name' => 'Anéis', 'slug' => 'aneis', 'image' => 'cat-aneis.jpg'],
        ['name' => 'Colares', 'slug' => 'colares', 'image' => 'cat-colares.jpg'],
        ['name' => 'Pulseiras', 'slug' => 'pulseiras', 'image' => 'cat-pulseiras.jpg'],
        ['name' => 'Brincos', 'slug' => 'brincos', 'image' => 'cat-brincos.jpg'],
        ['name' => 'Pingentes', 'slug' => 'pingentes', 'image' => 'cat-pingentes.jpg'],
    ];
}

/**
 * @return array<int, array{label:string,url:string}>
 */
function ampratas_nav_links(): array
{
    return [
        ['label' => 'Loja', 'url' => ampratas_shop_url()],
        ['label' => 'Alianças', 'url' => home_url('/product-category/aliancas')],
        ['label' => 'Anéis', 'url' => home_url('/product-category/aneis')],
        ['label' => 'Colares', 'url' => home_url('/product-category/colares')],
        ['label' => 'Pulseiras', 'url' => home_url('/product-category/pulseiras')],
        ['label' => 'Brincos', 'url' => home_url('/product-category/brincos')],
    ];
}

function ampratas_asset(string $path): string
{
    return get_template_directory_uri().'/assets/'.$path;
}

/**
 * Foto de cada categoria. Mesmos arquivos usados na vitrine Laravel (public/images).
 */
function ampratas_category_image(string $slug): string
{
    $file = match ($slug) {
        'aliancas' => 'cat-aliancas.jpg',
        'aneis' => 'cat-aneis.jpg',
        'colares' => 'cat-colares.jpg',
        'pulseiras' => 'cat-pulseiras.jpg',
        'brincos' => 'cat-brincos.jpg',
        'pingentes' => 'cat-pingentes.jpg',
        default => 'hero-aliancas.jpg',
    };

    return ampratas_asset('images/'.$file);
}

/**
 * Nome da primeira categoria do produto, ignorando "Uncategorized".
 */
function ampratas_product_category_name(WC_Product $product): string
{
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (! is_array($terms)) {
        return '';
    }

    foreach ($terms as $term) {
        if ($term->slug !== 'uncategorized') {
            return $term->name;
        }
    }

    return '';
}

/**
 * Card de produto no mesmo formato do partial product-card.blade.php da Laravel.
 */
function ampratas_product_card_html(WC_Product $product): string
{
    $image = $product->get_image('woocommerce_single', ['class' => 'h-full w-full object-cover', 'loading' => 'lazy']);
    $category = esc_html(ampratas_product_category_name($product));
    $name = esc_html($product->get_name());
    $price = wp_kses_post($product->get_price_html());
    $url = esc_url($product->get_permalink());

    return <<<HTML
<a href="{$url}" class="product-card group">
    <div class="product-media">{$image}</div>
    <div class="card-meta mt-3 flex items-start justify-between gap-3">
        <div>
            <p class="text-xs text-mute">{$category}</p>
            <h3 class="mt-1 font-display text-[1.35rem] leading-tight">{$name}</h3>
        </div>
        <div class="price card-price pt-4 text-right whitespace-nowrap">{$price}</div>
    </div>
</a>
HTML;
}

/**
 * Descrição curta do produto em texto puro, limitada a algumas palavras (blocos de destaque).
 */
function ampratas_short_text(WC_Product $product, int $words = 22): string
{
    $text = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());

    return wp_trim_words($text, $words, '…');
}

function ampratas_shop_url(): string
{
    if (function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('shop');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return home_url('/shop');
}

function ampratas_cart_url(): string
{
    if (function_exists('wc_get_cart_url')) {
        return wc_get_cart_url();
    }

    return home_url('/cart');
}

function ampratas_account_url(): string
{
    if (function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('myaccount');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    return home_url('/my-account');
}

function ampratas_is_current_url(string $url): bool
{
    $request = '';
    if (isset($GLOBALS['wp']) && is_object($GLOBALS['wp']) && isset($GLOBALS['wp']->request)) {
        $request = (string) $GLOBALS['wp']->request;
    }

    return trailingslashit($url) === trailingslashit(home_url('/'.$request));
}

function ampratas_cart_count_html(): string
{
    $count = 0;
    if (function_exists('WC') && WC()->cart) {
        $count = (int) WC()->cart->get_cart_contents_count();
    }

    // Mesmo formato da vitrine Laravel: "Sacola (2)", sem contador quando a sacola está vazia.
    if ($count < 1) {
        return '<b class="js-cart-count"></b>';
    }

    return '<b class="js-cart-count">('.esc_html((string) $count).')</b>';
}

if (class_exists('WooCommerce')) {
    require_once get_template_directory().'/inc/gateway/class-ampratas-payment-client.php';
    require_once get_template_directory().'/inc/gateway/class-wc-gateway-ampratas.php';

    if (class_exists(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class)) {
        require_once get_template_directory().'/inc/gateway/class-ampratas-gateway-blocks.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            static function ($registry): void {
                if (! is_object($registry) || ! method_exists($registry, 'register')) {
                    return;
                }
                if (method_exists($registry, 'is_registered') && $registry->is_registered('ampratas')) {
                    return;
                }
                $registry->register(new AMPratas_Gateway_Blocks());
            }
        );
    }

    add_filter('woocommerce_payment_gateways', static function (array $gateways): array {
        $gateways[] = 'WC_Gateway_AMPratas';

        return $gateways;
    });

    add_action('woocommerce_init', static function (): void {
        if (! is_array(get_option('woocommerce_ampratas_settings', null))) {
            update_option('woocommerce_ampratas_settings', [
                'enabled' => 'yes',
                'title' => 'Pix, cartão ou boleto',
                'description' => 'Pagamento seguro processado pela API da loja.',
                'mode' => 'demo',
                'api_url' => '',
                'public_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
            ], false);
        }

        if (get_option('ampratas_store_tuned') === '1') {
            return;
        }

        foreach (['bacs', 'cheque', 'cod'] as $gateway_id) {
            $settings = get_option('woocommerce_'.$gateway_id.'_settings', []);
            if (! is_array($settings)) {
                $settings = [];
            }
            $settings['enabled'] = 'no';
            update_option('woocommerce_'.$gateway_id.'_settings', $settings, false);
        }

        update_option('woocommerce_currency_pos', 'left_space');
        update_option('woocommerce_price_decimal_sep', ',');
        update_option('woocommerce_price_thousand_sep', '.');
        update_option('ampratas_store_tuned', '1', false);
    });

    add_action('rest_api_init', static function (): void {
        register_rest_route('ampratas/v1', '/payments/webhook', [
            'methods' => 'POST',
            'callback' => ['WC_Gateway_AMPratas', 'handle_rest'],
            'permission_callback' => '__return_true',
        ]);
    });

    add_action('woocommerce_admin_order_data_after_billing_address', static function ($order): void {
        if (! $order instanceof WC_Order) {
            return;
        }
        $payment_id = (string) $order->get_meta('_ampratas_payment_id');
        if ($payment_id === '') {
            return;
        }
        echo '<p><strong>ID do pagamento:</strong> '.esc_html($payment_id).'</p>';
    });

    add_action('woocommerce_before_shop_loop', static function (): void {
        echo '<div class="am-loop-bar">';
    }, 15);

    add_action('woocommerce_before_shop_loop', static function (): void {
        echo '</div>';
    }, 35);

    add_action('woocommerce_single_product_summary', static function (): void {
        echo '<ul class="am-assurances"><li>Prata 925</li><li>Frete sob consulta</li><li>Atendimento por WhatsApp</li></ul>';
    }, 35);

    add_action('woocommerce_review_order_before_submit', static function (): void {
        $settings = get_option('woocommerce_ampratas_settings', []);
        if (! is_array($settings) || ($settings['enabled'] ?? 'no') !== 'yes' || ($settings['mode'] ?? 'demo') === 'live') {
            return;
        }
        echo '<p class="am-pay-note">Modo demonstração: o pedido fica registrado e nenhuma cobrança é enviada.</p>';
    });

    // O cabeçalho da loja (kicker + título + descrição) já vem do woocommerce.php, como na Laravel.
    add_filter('woocommerce_show_page_title', '__return_false');

    add_filter('woocommerce_catalog_orderby', static function (array $options): array {
        return [
            'menu_order' => 'Em destaque',
            'popularity' => 'Mais vendidos',
            'rating' => 'Melhor avaliados',
            'date' => 'Mais recentes',
            'price' => 'Menor preço',
            'price-desc' => 'Maior preço',
        ];
    });

    add_filter('ngettext', static function (string $translation, string $single, string $plural, int $number, string $domain): string {
        if ($domain !== 'woocommerce') {
            return $translation;
        }
        if ($single === 'Showing the single result') {
            return 'Mostrando 1 resultado';
        }
        if ($plural === 'Showing all %1$d results') {
            return 'Mostrando os %1$d resultados';
        }
        if ($plural === 'Showing %1$d&ndash;%2$d of %3$d results') {
            return 'Mostrando %1$d–%2$d de %3$d resultados';
        }

        return $translation;
    }, 10, 5);

    add_filter('woocommerce_product_single_add_to_cart_text', static fn (): string => 'Adicionar à sacola');
    add_filter('woocommerce_product_add_to_cart_text', static fn (): string => 'Adicionar à sacola');
    add_filter('woocommerce_order_button_text', static fn (): string => 'Confirmar pedido');
    add_filter('woocommerce_product_related_products_heading', static fn (): string => 'Também na mesa');
    add_filter('loop_shop_columns', static fn (): int => 4);

    add_filter('woocommerce_output_related_products_args', static function (array $args): array {
        $args['posts_per_page'] = 4;
        $args['columns'] = 4;

        return $args;
    });

    add_filter('woocommerce_product_tabs', static function (array $tabs): array {
        if (isset($tabs['description'])) {
            $tabs['description']['title'] = 'Detalhes';
        }
        if (isset($tabs['additional_information'])) {
            $tabs['additional_information']['title'] = 'Informações';
        }
        if (isset($tabs['reviews'])) {
            $tabs['reviews']['title'] = 'Avaliações';
        }

        return $tabs;
    });

    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

    add_action('template_redirect', static function (): void {
        if (! isset($_GET['ampratas_empty_cart'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (! wp_verify_nonce($nonce, 'ampratas_empty_cart')) {
            wp_safe_redirect(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart'));
            exit;
        }

        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
        }

        wp_safe_redirect(wc_get_cart_url());
        exit;
    });

    add_filter('render_block', static function (string $content, array $block): string {
        if (($block['blockName'] ?? '') !== 'woocommerce/cart') {
            return $content;
        }
        if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
            return $content;
        }

        $url = wp_nonce_url(
            add_query_arg('ampratas_empty_cart', '1', wc_get_cart_url()),
            'ampratas_empty_cart'
        );

        $link = sprintf(
            '<p class="am-clear-cart"><a href="%s" onclick="return confirm(\'Tirar todas as peças da sacola?\');">Limpar sacola</a></p>',
            esc_url($url)
        );

        return $link.$content;
    }, 10, 2);
}
