<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Com MERCADOPAGO_TEST_PAYER_EMAIL definido, o comprador da preferência vira o comprador de teste.
 * Sem isso, o Mercado Pago recusa pagamento de um e-mail de conta real para um vendedor de teste.
 *
 * Contas de teste com credenciais APP_USR só funcionam pelo link normal (init_point), então o plugin
 * roda em modo produção com as credenciais do vendedor de teste. Por isso a troca depende só da
 * variável: deixe-a vazia em produção de verdade.
 *
 * O ajuste vale só para a leitura feita pelo plugin do Mercado Pago. O e-mail salvo no pedido
 * e os e-mails do WooCommerce continuam como foram digitados no checkout.
 */
add_filter('woocommerce_order_get_billing_email', static function (string $email, $order): string {
    $testEmail = (string) getenv('MERCADOPAGO_TEST_PAYER_EMAIL');
    if ($testEmail === '') {
        return $email;
    }

    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25) as $frame) {
        $class = $frame['class'] ?? '';
        if (is_string($class) && str_starts_with($class, 'MercadoPago\\Woocommerce\\')) {
            return $testEmail;
        }
    }

    return $email;
}, 10, 2);
