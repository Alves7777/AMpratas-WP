<?php
get_header();
?>

<?php
$am_wide = function_exists('is_cart') && (is_cart() || is_checkout());
?>
<section class="<?php echo $am_wide ? 'mx-auto max-w-6xl px-5 py-16' : 'mx-auto max-w-3xl px-5 py-16'; ?>">
    <h1 class="am-page-title"><?php the_title(); ?></h1>
    <div class="entry-content mt-8">
        <?php
        while (have_posts()) {
            the_post();
            the_content();
        }
        ?>
    </div>
</section>

<?php
get_footer();
