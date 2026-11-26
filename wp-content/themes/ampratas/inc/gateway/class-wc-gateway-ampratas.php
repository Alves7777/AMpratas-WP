<?php

/**
 * Gateway WooCommerce da AMpratas.
 *
 * Modo demonstração registra o pedido e não chama a API.
 * Modo produção envia o pedido para a URL configurada e espera o webhook confirmar.
 */
class WC_Gateway_AMPratas extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'ampratas';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = 'AMpratas — Pagamentos';
        $this->method_description = 'Checkout pronto para a API de pagamento. Em demonstração, o pedido é gravado e nada é cobrado. Em produção, o cliente é enviado ao link devolvido pela API e o webhook em <code>/wp-json/ampratas/v1/payments/webhook</code> confirma o pagamento. O formato do pedido fica em <code>AMPratas_Payment_Client</code>.';
        $this->supports = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_ampratas_webhook', [$this, 'handle_legacy_webhook']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Ativar',
                'type' => 'checkbox',
                'label' => 'Mostrar AMpratas no checkout',
                'default' => 'yes',
            ],
            'title' => [
                'title' => 'Título no checkout',
                'type' => 'text',
                'default' => 'Pix, cartão ou boleto',
            ],
            'description' => [
                'title' => 'Descrição',
                'type' => 'textarea',
                'default' => 'Pagamento seguro processado pela API da loja.',
            ],
            'mode' => [
                'title' => 'Modo',
                'type' => 'select',
                'default' => 'demo',
                'options' => [
                    'demo' => 'Demonstração — registra o pedido, não cobra',
                    'live' => 'Produção — chama a API',
                ],
            ],
            'api_url' => [
                'title' => 'URL do endpoint',
                'type' => 'text',
                'description' => 'POST que cria a cobrança. Ex.: https://api.seuprovedor.com/v1/checkouts',
                'default' => '',
                'placeholder' => 'https://',
            ],
            'public_key' => [
                'title' => 'Chave pública',
                'type' => 'text',
                'default' => '',
            ],
            'secret_key' => [
                'title' => 'Chave secreta',
                'type' => 'password',
                'description' => 'Enviada como Authorization: Bearer. Não aparece de novo depois de salva.',
                'default' => '',
            ],
            'webhook_secret' => [
                'title' => 'Segredo do webhook',
                'type' => 'password',
                'description' => 'A API deve assinar o corpo cru com HMAC-SHA256 e enviar em X-AMPratas-Signature. Sem este segredo o webhook recusa tudo.',
                'default' => '',
            ],
        ];
    }

    public function is_available(): bool
    {
        if ($this->get_option('enabled') !== 'yes') {
            return false;
        }

        return parent::is_available();
    }

    public function get_description()
    {
        $description = (string) parent::get_description();
        if ($this->get_option('mode') !== 'live') {
            $description = trim($description.' Pedido em demonstração: nenhuma cobrança é enviada.');
        }

        return $description;
    }

    /**
     * @param int $order_id
     * @return array<string, string>
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            wc_add_notice('Não foi possível abrir o pedido.', 'error');

            return ['result' => 'failure'];
        }

        if ($this->get_option('mode') !== 'live') {
            $order->update_status('on-hold', 'Modo demonstração: pedido registrado, nenhuma API de pagamento foi chamada.');

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        $result = $this->client()->create_checkout($order);
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');

            return ['result' => 'failure'];
        }

        $order->update_meta_data('_ampratas_payment_id', $result['id']);
        $order->update_status('pending', 'Checkout criado na API. Referência '.$result['id'].'.');
        $order->save();

        return [
            'result' => 'success',
            'redirect' => $result['checkout_url'],
        ];
    }

    public static function handle_rest(WP_REST_Request $request): WP_REST_Response
    {
        $settings = get_option('woocommerce_ampratas_settings', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $client = new AMPratas_Payment_Client(
            (string) ($settings['api_url'] ?? ''),
            (string) ($settings['public_key'] ?? ''),
            (string) ($settings['secret_key'] ?? ''),
            (string) ($settings['webhook_secret'] ?? ''),
        );

        $parsed = $client->parse_webhook($request);
        if (is_wp_error($parsed)) {
            $status = (int) ($parsed->get_error_data()['status'] ?? 400);

            return new WP_REST_Response([
                'ok' => false,
                'error' => $parsed->get_error_message(),
            ], $status > 0 ? $status : 400);
        }

        $order = wc_get_order((int) $parsed['reference']);
        if (! $order instanceof WC_Order) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Pedido não encontrado.'], 404);
        }

        if ($parsed['id'] !== '') {
            $order->update_meta_data('_ampratas_payment_id', $parsed['id']);
        }

        switch ($parsed['status']) {
            case 'paid':
            case 'approved':
            case 'succeeded':
            case 'authorized':
                if (! $order->is_paid()) {
                    $order->payment_complete($parsed['id'] !== '' ? $parsed['id'] : '');
                }
                break;
            case 'pending':
            case 'in_process':
            case 'processing':
                $order->update_status('on-hold', 'Webhook: pagamento ainda pendente.');
                break;
            case 'failed':
            case 'rejected':
            case 'cancelled':
            case 'canceled':
            case 'refunded':
                $order->update_status('failed', 'Webhook: pagamento não aprovado ('.$parsed['status'].').');
                break;
            default:
                $order->add_order_note('Webhook com status não mapeado: '.$parsed['status'].'.');
                $order->save();
        }

        return new WP_REST_Response(['ok' => true], 200);
    }

    public function handle_legacy_webhook(): void
    {
        status_header(410);
        exit;
    }

    private function client(): AMPratas_Payment_Client
    {
        return new AMPratas_Payment_Client(
            (string) $this->get_option('api_url'),
            (string) $this->get_option('public_key'),
            (string) $this->get_option('secret_key'),
            (string) $this->get_option('webhook_secret'),
        );
    }
}
