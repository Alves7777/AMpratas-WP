<?php
get_header();

// Mesma ordem da home Laravel (store/home.blade.php):
// vitrine (destaque + 2 ofertas), promessas, categorias, prateleira, pulseiras, Instagram e "Chegou agora".
$latest = wc_get_products([
    'status' => 'publish',
    'limit' => 8,
    'orderby' => 'date',
    'order' => 'DESC',
    'visibility' => 'visible',
]);

$featured = wc_get_products([
    'status' => 'publish',
    'limit' => 6,
    'featured' => true,
    'orderby' => 'date',
    'order' => 'DESC',
    'visibility' => 'visible',
]);
if (empty($featured)) {
    $featured = $latest;
}

$poster = $latest[0] ?? null;
$offers = array_slice($latest, 1, 2);

$lead = $featured[0] ?? null;
$shelf = array_slice($featured, 1, 5);

// Só mostra categorias que existem no WooCommerce.
$categories = array_values(array_filter(
    ampratas_category_cards(),
    static fn (array $category): bool => ! empty(term_exists($category['slug'], 'product_cat'))
));
?>

<?php if ($poster) : ?>
<section class="container-luxe shop-window">
    <a href="<?php echo esc_url($poster->get_permalink()); ?>" class="shop-hero">
        <?php echo $poster->get_image('woocommerce_single', ['loading' => 'eager']); ?>
        <div class="shop-hero-copy">
            <p><?php echo esc_html(ampratas_product_category_name($poster) ?: 'Prata 925'); ?> · pronta entrega</p>
            <h1><?php echo esc_html($poster->get_name()); ?></h1>
            <p class="poster-price"><?php echo wp_kses_post($poster->get_price_html()); ?></p>
            <p class="shop-hero-text"><?php echo esc_html(ampratas_short_text($poster)); ?></p>
            <span class="btn-primary">Comprar</span>
        </div>
    </a>

    <?php if ($offers) : ?>
        <div class="shop-side">
            <?php foreach ($offers as $offer) : ?>
                <a href="<?php echo esc_url($offer->get_permalink()); ?>" class="side-offer">
                    <?php echo $offer->get_image('woocommerce_single'); ?>
                    <div>
                        <p><?php echo esc_html(ampratas_product_category_name($offer)); ?></p>
                        <h2><?php echo esc_html($offer->get_name()); ?></h2>
                        <p class="side-price"><?php echo wp_kses_post($offer->get_price_html()); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="container-luxe">
    <ul class="promises">
        <li>
            <strong>Fabricação própria</strong>
            <span>A peça sai da nossa bancada, em Fortaleza.</span>
        </li>
        <li>
            <strong>Garantia na cor</strong>
            <span>O brilho da prata permanece com você.</span>
        </li>
        <li>
            <strong>Todo o Brasil</strong>
            <span>Enviamos com embalagem de presente.</span>
        </li>
    </ul>
</section>

<?php if ($categories) : ?>
<section class="container-luxe pb-6 pt-14 lg:pt-20">
    <div class="mb-6 flex items-end justify-between gap-6">
        <h2 class="section-title">O que tem na loja</h2>
        <a href="<?php echo esc_url(ampratas_shop_url()); ?>" class="text-link">Tudo</a>
    </div>

    <div class="cat-scroll">
        <?php foreach ($categories as $category) : ?>
            <a href="<?php echo esc_url(home_url('/product-category/'.$category['slug'])); ?>" class="cat-card">
                <img src="<?php echo esc_url(ampratas_category_image($category['slug'])); ?>" alt="<?php echo esc_attr($category['name']); ?>">
                <span><?php echo esc_html($category['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="cat-board">
        <?php foreach ($categories as $category) : ?>
            <a href="<?php echo esc_url(home_url('/product-category/'.$category['slug'])); ?>">
                <img src="<?php echo esc_url(ampratas_category_image($category['slug'])); ?>" alt="">
                <span><?php echo esc_html($category['name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($lead) : ?>
<section class="container-luxe py-16 lg:py-24">
    <h2 class="section-title mb-8">Na vitrine</h2>
    <div class="shelf">
        <a href="<?php echo esc_url($lead->get_permalink()); ?>" class="shelf-lead">
            <?php echo $lead->get_image('woocommerce_single'); ?>
            <div class="mt-4 flex items-baseline justify-between gap-4">
                <h3 class="font-display text-3xl leading-none"><?php echo esc_html($lead->get_name()); ?></h3>
                <span class="text-sm"><?php echo wp_kses_post($lead->get_price_html()); ?></span>
            </div>
            <p class="mt-2 text-sm text-mute"><?php echo esc_html(ampratas_product_category_name($lead)); ?></p>
        </a>

        <div>
            <?php foreach ($shelf as $product) : ?>
                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="shelf-item">
                    <?php echo $product->get_image('woocommerce_single'); ?>
                    <span>
                        <span class="block font-display text-xl leading-tight"><?php echo esc_html($product->get_name()); ?></span>
                        <span class="mt-1 block text-sm text-mute"><?php echo esc_html(ampratas_product_category_name($product)); ?></span>
                    </span>
                    <span class="shelf-price text-sm"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?php echo esc_url(ampratas_shop_url()); ?>" class="text-link mt-6">Ver o restante da coleção</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container-luxe pb-4">
    <div class="grid items-center gap-8 border-y border-ink/10 py-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-14">
        <img src="<?php echo esc_url(ampratas_asset('ig/05-nov21.jpg')); ?>" alt="Pulseiras AMpratas na caixa" class="soft-photo">
        <div>
            <h2 class="section-title">Pulseiras pra usar juntas ou sozinhas.</h2>
            <p class="mt-4 max-w-md text-ink/75 leading-relaxed">
                Correntes finas, trevos e peças de empilhar. O brilho fica no metal, sem exagero.
            </p>
            <a href="<?php echo esc_url(home_url('/product-category/pulseiras')); ?>" class="text-link mt-6">Ver pulseiras</a>
        </div>
    </div>
</section>

<section class="container-luxe py-12 lg:py-16">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h2 class="section-title">No Instagram</h2>
        <a href="https://www.instagram.com/ampratass__/" class="text-link" target="_blank" rel="noopener">@ampratass__</a>
    </div>
    <div class="ig-grid">
        <?php
        $instagram = [
            ['ig/05-nov21.jpg', 'Pulseiras na caixa'],
            ['ig/04-aug14-2025.jpg', 'Colar com fotogravação'],
            ['ig/07-nov10.jpg', 'Alianças personalizadas'],
            ['ig/03-sep22.jpg', 'Alianças em uso'],
            ['ig/02-aug13.jpg', 'Clientes AMpratas'],
        ];
        foreach ($instagram as [$photo, $alt]) : ?>
            <a href="https://www.instagram.com/ampratass__/" target="_blank" rel="noopener">
                <img src="<?php echo esc_url(ampratas_asset($photo)); ?>" alt="<?php echo esc_attr($alt); ?>">
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($latest) : ?>
<section class="container-luxe py-16 lg:py-20">
    <div class="mb-8 flex items-end justify-between">
        <h2 class="section-title">Chegou agora</h2>
    </div>
    <div class="grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach (array_slice($latest, 0, 4) as $product) : ?>
            <?php echo ampratas_product_card_html($product); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
get_footer();
