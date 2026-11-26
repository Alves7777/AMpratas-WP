<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * E-mail da loja.
 *
 * O SMTP vem das variáveis de ambiente do container (MAIL_HOST, MAIL_PORT, MAIL_USERNAME,
 * MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME), definidas no .env do projeto.
 * Em desenvolvimento aponte para o Mailtrap Sandbox; em produção, para o SMTP real.
 */
function ampratas_mail_env(string $key, string $default = ''): string
{
    $value = getenv($key);

    return ($value === false || $value === '') ? $default : (string) $value;
}

add_action('phpmailer_init', static function ($phpmailer): void {
    $host = ampratas_mail_env('MAIL_HOST');
    if ($host === '') {
        return;
    }

    $username = ampratas_mail_env('MAIL_USERNAME');

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = (int) ampratas_mail_env('MAIL_PORT', '2525');
    $phpmailer->SMTPAuth = $username !== '';
    $phpmailer->Username = $username;
    $phpmailer->Password = ampratas_mail_env('MAIL_PASSWORD');
});

add_filter('wp_mail_from', static function (string $from): string {
    return ampratas_mail_env('MAIL_FROM_ADDRESS', $from);
});

add_filter('wp_mail_from_name', static function (string $name): string {
    return ampratas_mail_env('MAIL_FROM_NAME', $name);
});

/**
 * Uma vez por instalação: remetente do WooCommerce e aviso de "Novo pedido" para o dono da loja.
 * Depois disso, ajuste em WooCommerce → Configurações → E-mails.
 */
add_action('woocommerce_init', static function (): void {
    if (get_option('ampratas_mail_tuned') === '1') {
        return;
    }

    $owner = ampratas_mail_env('MAIL_FROM_ADDRESS', 'contato@ampratas.com');

    update_option('woocommerce_email_from_address', $owner);
    update_option('woocommerce_email_from_name', ampratas_mail_env('MAIL_FROM_NAME', 'AMpratas'));

    $new_order = get_option('woocommerce_new_order_settings', []);
    if (! is_array($new_order)) {
        $new_order = [];
    }
    $new_order['enabled'] = 'yes';
    $new_order['recipient'] = $owner;
    update_option('woocommerce_new_order_settings', $new_order, false);

    update_option('ampratas_mail_tuned', '1', false);
});
