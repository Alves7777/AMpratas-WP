<?php

declare(strict_types=1);

/**
 * Cliente HTTP da API de pagamento.
 *
 * O checkout de produção chama create_checkout(). O webhook chama parse_webhook().
 * Esses dois métodos são o ponto de adaptação: troque o corpo do pedido e a leitura
 * da resposta quando o provedor estiver definido (Mercado Pago, Pagar.me, Stripe, API própria).
 *
 * Contrato padrão
 * ---------------
 * POST {url configurada em WooCommerce → Pagamentos → AMpratas}
 * Authorization: Bearer {chave secreta}
 * X-Public-Key: {chave pública}
 * Content-Type: application/json
 *
 * {
 *   "reference": "123",
 *   "amount": "289.90",
 *   "amount_cents": 28990,
 *   "currency": "BRL",
 *   "description": "Pedido #123 — AMpratas",
 *   "customer": {"name","email","phone","document"},
 *   "items": [{"name","quantity","unit_amount"}],
 *   "shipping_amount": "0.00",
 *   "return_url": "https://loja/checkout/order-received/123/?key=...",
 *   "webhook_url": "https://loja/wp-json/ampratas/v1/payments/webhook"
 * }
 *
 * Resposta esperada (também aceita payment_url, url, init_point):
 * { "id": "pay_xxx", "checkout_url": "https://..." }
 *
 * Webhook POST em /wp-json/ampratas/v1/payments/webhook
 * Header X-AMPratas-Signature: hex hmac-sha256 do corpo cru, usando o segredo do webhook.
 * { "reference": "123", "id": "pay_xxx", "status": "paid|pending|failed|cancelled" }
 */
class AMPratas_Payment_Client
{
    public function __construct(
        private string $api_url,
        private string $public_key,
        private string $secret_key,
        private string $webhook_secret,
    ) {
    }

    public function is_configured(): bool
    {
        return $this->api_url !== '' && $this->secret_key !== '' && wp_http_validate_url($this->api_url) !== false;
    }

    /**
     * @return array{id:string,checkout_url:string}|WP_Error
     */
    public function create_checkout(WC_Order $order)
    {
        if (! $this->is_configured()) {
            return new WP_Error(
                'ampratas_not_configured',
                'Pagamento indisponível. Em WooCommerce → Pagamentos → AMpratas, informe a URL e a chave secreta da API.'
            );
        }

        $response = wp_remote_post($this->api_url, [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer '.$this->secret_key,
                'X-Public-Key' => $this->public_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => wp_json_encode($this->build_payload($order)),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('ampratas_http', 'Não foi possível falar com a API de pagamento. Tente de novo em instantes.');
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $json = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || ! is_array($json)) {
            return new WP_Error('ampratas_api', 'A API de pagamento recusou a cobrança. Confira a URL, as chaves e o formato do pedido.');
        }

        $id = $this->first_string($json, ['id', 'payment_id', 'preference_id']);
        $url = $this->first_string($json, ['checkout_url', 'payment_url', 'url', 'init_point', 'sandbox_init_point']);

        if ($id === '' || $url === '' || wp_http_validate_url($url) === false) {
            return new WP_Error('ampratas_api_shape', 'A API respondeu sem um link de pagamento. Ajuste a leitura da resposta em AMPratas_Payment_Client.');
        }

        return [
            'id' => $id,
            'checkout_url' => $url,
        ];
    }

    /**
     * @return array{reference:string,id:string,status:string}|WP_Error
     */
    public function parse_webhook(WP_REST_Request $request)
    {
        if ($this->webhook_secret === '') {
            return new WP_Error('ampratas_webhook_secret', 'Segredo do webhook não configurado.', ['status' => 401]);
        }

        $raw = $request->get_body();
        $given = strtolower(trim((string) $request->get_header('x_ampratas_signature')));
        $given = str_starts_with($given, 'sha256=') ? substr($given, 7) : $given;
        $expected = hash_hmac('sha256', $raw, $this->webhook_secret);

        if ($given === '' || ! hash_equals($expected, $given)) {
            return new WP_Error('ampratas_webhook_signature', 'Assinatura do webhook inválida.', ['status' => 401]);
        }

        $json = $request->get_json_params();
        if (! is_array($json)) {
            $json = json_decode($raw, true);
        }
        if (! is_array($json)) {
            return new WP_Error('ampratas_webhook_body', 'Corpo do webhook inválido.', ['status' => 400]);
        }

        $reference = $this->first_string($json, ['reference', 'order_id', 'external_reference']);
        $status = strtolower($this->first_string($json, ['status', 'payment_status']));
        $id = $this->first_string($json, ['id', 'payment_id']);

        if ($reference === '' || $status === '') {
            return new WP_Error('ampratas_webhook_fields', 'Webhook sem referência ou status.', ['status' => 422]);
        }

        return [
            'reference' => $reference,
            'id' => $id,
            'status' => $status,
        ];
    }

    /**
     * Monte aqui o JSON no formato do provedor.
     *
     * @return array<string, mixed>
     */
    private function build_payload(WC_Order $order): array
    {
        $items = [];
        foreach ($order->get_items() as $item) {
            if (! $item instanceof WC_Order_Item_Product) {
                continue;
            }
            $quantity = max(1, (int) $item->get_quantity());
            $items[] = [
                'name' => $item->get_name(),
                'quantity' => $quantity,
                'unit_amount' => wc_format_decimal(((float) $item->get_total()) / $quantity, 2),
            ];
        }

        $document = (string) $order->get_meta('_billing_cpf');
        if ($document === '') {
            $document = (string) $order->get_meta('billing_cpf');
        }

        return [
            'reference' => (string) $order->get_id(),
            'amount' => wc_format_decimal($order->get_total(), 2),
            'amount_cents' => (int) round(((float) $order->get_total()) * 100),
            'currency' => $order->get_currency(),
            'description' => sprintf('Pedido #%d — AMpratas', $order->get_id()),
            'customer' => [
                'name' => trim($order->get_formatted_billing_full_name()),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'document' => $document,
            ],
            'billing' => [
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ],
            'items' => $items,
            'shipping_amount' => wc_format_decimal($order->get_shipping_total(), 2),
            'return_url' => $order->get_checkout_order_received_url(),
            'webhook_url' => rest_url('ampratas/v1/payments/webhook'),
        ];
    }

    /**
     * @param array<string, mixed> $json
     * @param array<int, string> $keys
     */
    private function first_string(array $json, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($json[$key]) && (is_string($json[$key]) || is_numeric($json[$key]))) {
                return trim((string) $json[$key]);
            }
        }

        return '';
    }
}
