<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class AMPratas_Gateway_Blocks extends AbstractPaymentMethodType
{
    protected $name = 'ampratas';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_ampratas_settings', []);
        if (! is_array($this->settings)) {
            $this->settings = [];
        }
    }

    public function is_active(): bool
    {
        return ($this->settings['enabled'] ?? 'no') === 'yes';
    }

    public function get_payment_method_script_handles(): array
    {
        $path = get_template_directory().'/assets/js/blocks-checkout.js';
        wp_register_script(
            'ampratas-blocks',
            get_template_directory_uri().'/assets/js/blocks-checkout.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'],
            file_exists($path) ? (string) filemtime($path) : '1.0.0',
            true
        );

        return ['ampratas-blocks'];
    }

    public function get_payment_method_data(): array
    {
        $description = (string) ($this->settings['description'] ?? '');
        if (($this->settings['mode'] ?? 'demo') !== 'live') {
            $description = trim($description.' Pedido em demonstração: nenhuma cobrança é enviada.');
        }

        return [
            'title' => (string) ($this->settings['title'] ?? 'Pix, cartão ou boleto'),
            'description' => $description,
            'supports' => ['products'],
        ];
    }
}

