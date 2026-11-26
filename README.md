# AMpratas — WordPress + WooCommerce (paralelo)

Prova de conceito em paralelo ao projeto Laravel/Filament (`../AMpratas`).

| Projeto | Stack | Porta |
|---------|-------|-------|
| `AMpratas` | Laravel + Filament | http://localhost:8090 |
| `AMpratas-wp` | WordPress + WooCommerce | http://localhost:8091 |

## Subir

```bash
cd ~/Documents/AMpratas-wp
docker compose up -d
# o container wpcli roda o bootstrap (instala WP, Woo, tema, produtos)
docker compose logs -f wpcli
```

## Acessos

- **Loja:** http://localhost:8091
- **Admin WP:** http://localhost:8091/wp-admin
  - user: `admin`
  - senha: `ampratas123`
- **MySQL:** `127.0.0.1:3312`

## Pagamento

O checkout já tem o gateway **AMpratas — Pagamentos** (WooCommerce → Pagamentos).

- **Demonstração** (padrão): o pedido é gravado como aguardando e nada é cobrado.
- **Produção:** informe a URL do POST, a chave secreta e o segredo do webhook. O formato do JSON e a leitura da resposta ficam em `wp-content/themes/ampratas/inc/gateway/class-ampratas-payment-client.php`.
- Webhook: `POST /wp-json/ampratas/v1/payments/webhook` com header `X-AMPratas-Signature` (HMAC-SHA256 do corpo).

## O que vem pronto

- Tema `ampratas` (visual alinhado à marca)
- WooCommerce ativado
- Categorias (Alianças, Anéis, Colares…)
- Produtos demo com fotos da marca

## Comparar com o Laravel

- Laravel (`8090`): admin Filament custom + arquitetura limpa + MinIO
- WordPress (`8091`): admin nativo Woo + plugins de pagamento/frete prontos

Os dois rodam ao mesmo tempo para você decidir o caminho.
