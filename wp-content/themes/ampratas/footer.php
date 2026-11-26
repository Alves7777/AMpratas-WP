</main>

<footer class="site-footer">
    <div class="container-luxe">
        <div class="footer-grid">
            <p>Prata com fabricação própria em Fortaleza. Embalagem de presente e atendimento direto.</p>
            <div>
                <p class="footer-label">Atendimento</p>
                <div class="contact-links">
                    <?php get_template_part('template-parts/contact-icons'); ?>
                </div>
                <a class="contact-phone" href="tel:+558596744989">+55 85 9674-4989</a>
                <span>Fortaleza, CE</span>
            </div>
            <div>
                <p class="footer-label">Loja</p>
                <a href="<?php echo esc_url(ampratas_shop_url()); ?>">Catálogo</a>
                <a href="<?php echo esc_url(ampratas_cart_url()); ?>">Sacola</a>
                <a href="<?php echo esc_url(ampratas_account_url()); ?>">Minha conta</a>
            </div>
        </div>
    </div>
    <div class="footer-copy">© <?php echo esc_html(gmdate('Y')); ?> AMpratas</div>
</footer>

</div>
<?php wp_footer(); ?>
</body>
</html>
