<?php
$nav_links = ampratas_nav_links();
$cart_url = ampratas_cart_url();
$account_url = ampratas_account_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">

<a class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[80] focus:rounded-full focus:bg-pearl focus:px-4 focus:py-2" href="#conteudo">Pular para o conteúdo</a>

<header id="site-header" class="site-header">
    <div class="container-luxe header-bar">
        <div class="header-side header-side-left">
            <button type="button" class="menu-btn" data-toggle-nav aria-label="Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M4 12h16M4 17h12"/></svg>
            </button>
            <nav class="header-nav" aria-label="Principal">
                <?php foreach (array_slice($nav_links, 0, 3) as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="nav-link"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="brand-lockup" aria-label="AMpratas — Pratas e Acessórios">
            <img src="<?php echo esc_url(ampratas_asset('brand/logo-mark-wide.png')); ?>" alt="AMpratas — Pratas e Acessórios" class="brand-logo">
        </a>

        <div class="header-side header-side-right">
            <nav class="header-nav" aria-label="Categorias">
                <?php foreach (array_slice($nav_links, 3) as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" class="nav-link"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="header-contact">
                <?php get_template_part('template-parts/contact-icons'); ?>
            </div>
            <a href="<?php echo esc_url($cart_url); ?>" class="bag-link">
                Sacola <?php echo ampratas_cart_count_html(); ?>
            </a>
        </div>
    </div>

    <div id="mobile-nav" class="mobile-nav hidden lg:hidden">
        <div class="container-luxe flex flex-col py-2 text-base">
            <?php foreach ($nav_links as $link) : ?>
                <a class="py-3" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
            <?php endforeach; ?>
            <a class="py-3" href="<?php echo esc_url($account_url); ?>">Minha conta</a>
            <div class="mobile-contact">
                <?php get_template_part('template-parts/contact-icons'); ?>
                <a href="tel:+558596744989">+55 85 9674-4989</a>
            </div>
        </div>
    </div>
</header>

<main id="conteudo" class="flex-1 site-main">
