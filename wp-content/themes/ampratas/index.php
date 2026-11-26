<?php
get_header();
?>

<section class="mx-auto max-w-3xl px-5 py-16">
    <h1 class="am-page-title"><?php echo esc_html(get_the_title() ?: 'AMpratas'); ?></h1>
    <div class="entry-content mt-8">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class(); ?>>
                    <?php the_content(); ?>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <p class="text-mute">Nenhum conteúdo encontrado.</p>
        <?php endif; ?>
    </div>
</section>

<?php
get_footer();
