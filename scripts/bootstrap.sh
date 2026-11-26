#!/bin/sh
set -e

echo "[AMpratas WP] Aguardando WordPress..."
i=0
until wp core is-installed --allow-root 2>/dev/null || [ $i -ge 60 ]; do
  i=$((i + 1))
  sleep 2
done

if ! wp core is-installed --allow-root 2>/dev/null; then
  echo "[AMpratas WP] Instalando WordPress..."
  wp core install \
    --url="http://localhost:8091" \
    --title="AMpratas" \
    --admin_user="admin" \
    --admin_password="ampratas123" \
    --admin_email="admin@ampratas.com" \
    --skip-email \
    --allow-root
fi

wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

echo "[AMpratas WP] Instalando WooCommerce..."
# WP 6.7: última Woo “latest” exige WP 7+; fixamos 9.6.2
if ! wp plugin is-active woocommerce --allow-root 2>/dev/null; then
  wp plugin install woocommerce --version=9.6.2 --activate --allow-root || \
  wp plugin activate woocommerce --allow-root || true
fi


wp theme activate ampratas --allow-root || true

wp option update blogdescription "Pratas e Acessórios" --allow-root
wp option update woocommerce_currency "BRL" --allow-root
wp option update woocommerce_default_country "BR:CE" --allow-root
wp option update woocommerce_store_address "Fortaleza" --allow-root
wp option update woocommerce_store_city "Fortaleza" --allow-root
wp option update woocommerce_calc_taxes "no" --allow-root
wp option update woocommerce_enable_guest_checkout "yes" --allow-root
wp option update woocommerce_coming_soon "no" --allow-root
wp option update woocommerce_store_pages_only "no" --allow-root
wp language core install pt_BR --activate --allow-root || true
wp language plugin install woocommerce pt_BR --allow-root || true
wp site switch-language pt_BR --allow-root || true
wp option update show_on_front "page" --allow-root

# Páginas Woo padrão
wp wc tool run install_pages --user=1 --allow-root 2>/dev/null || true

# Home page
HOME_ID=$(wp post list --post_type=page --name=inicio --field=ID --allow-root 2>/dev/null || true)
if [ -z "$HOME_ID" ]; then
  HOME_ID=$(wp post create --post_type=page --post_title='Início' --post_name='inicio' --post_status=publish --porcelain --allow-root)
fi
wp option update page_on_front "$HOME_ID" --allow-root

SHOP_ID=$(wp post list --post_type=page --pagename=shop --field=ID --allow-root 2>/dev/null || true)
if [ -n "$SHOP_ID" ]; then
  wp option update page_for_posts "0" --allow-root
fi

# Categorias
create_cat() {
  NAME="$1"
  SLUG="$2"
  EXISTS=$(wp term list product_cat --slug="$SLUG" --field=term_id --allow-root 2>/dev/null || true)
  if [ -z "$EXISTS" ]; then
    wp wc product_cat create --name="$NAME" --slug="$SLUG" --user=1 --allow-root >/dev/null 2>&1 || \
    wp term create product_cat "$NAME" --slug="$SLUG" --allow-root >/dev/null
  fi
}

create_cat "Alianças" "aliancas"
create_cat "Anéis" "aneis"
create_cat "Colares" "colares"
create_cat "Pulseiras" "pulseiras"
create_cat "Brincos" "brincos"
create_cat "Pingentes" "pingentes"

# Produtos demo (só se ainda não houver)
COUNT=$(wp post list --post_type=product --format=count --allow-root 2>/dev/null || echo 0)
if [ "$COUNT" = "0" ] || [ -z "$COUNT" ]; then
  echo "[AMpratas WP] Criando produtos demo..."

  create_product() {
    TITLE="$1"
    PRICE="$2"
    CAT="$3"
    DESC="$4"
    IMG="$5"

    PID=$(wp wc product create \
      --name="$TITLE" \
      --type=simple \
      --regular_price="$PRICE" \
      --description="$DESC" \
      --short_description="$DESC" \
      --categories="[{\"slug\":\"$CAT\"}]" \
      --user=1 \
      --porcelain \
      --allow-root 2>/dev/null || true)

    if [ -z "$PID" ]; then
      PID=$(wp post create --post_type=product --post_title="$TITLE" --post_status=publish --porcelain --allow-root)
      wp post meta update "$PID" _regular_price "$PRICE" --allow-root
      wp post meta update "$PID" _price "$PRICE" --allow-root
      wp post meta update "$PID" _manage_stock "yes" --allow-root
      wp post meta update "$PID" _stock "10" --allow-root
      TERM=$(wp term list product_cat --slug="$CAT" --field=term_id --allow-root)
      [ -n "$TERM" ] && wp post term set "$PID" product_cat "$TERM" --by=id --allow-root
    fi

    if [ -n "$PID" ]; then
      wp post term set "$PID" product_cat "$CAT" --by=slug --allow-root >/dev/null || true
    fi

    if [ -n "$PID" ] && [ -f "/var/www/html/wp-content/themes/ampratas/assets/images/$IMG" ]; then
      MID=$(wp media import "/var/www/html/wp-content/themes/ampratas/assets/images/$IMG" --porcelain --allow-root 2>/dev/null || true)
      if [ -n "$MID" ]; then
        wp post meta update "$PID" _thumbnail_id "$MID" --allow-root
      fi
    fi
  }

  create_product "Par Aliança Fosca com Frisos" "289.90" "aliancas" "Par de alianças em prata 925 com acabamento fosco." "product-alianca-fosca.jpg"
  create_product "Conjunto Solitário + Aliança" "349.90" "aliancas" "Conjunto romântico com solitário e aliança." "product-solitario-set.jpg"
  create_product "Anéis Iniciais Personalizados" "149.90" "aneis" "Par de anéis delicados com iniciais." "product-iniciais.jpg"
  create_product "Colar Rubi Halo Quadrado" "199.90" "colares" "Pingente com pedra vermelha e halo de zircônias." "product-colar-rubi.jpg"
  create_product "Colar Turquesa com Halo" "189.90" "colares" "Pingente com pedra verde-água." "product-colar-turquesa.jpg"
  create_product "Pulseira Singapore Delicada" "89.90" "pulseiras" "Corrente singapore fina com alto brilho." "product-singapore.jpg"
  create_product "Trio Pulseiras Coração e Esferas" "219.90" "pulseiras" "Conjunto stackable em prata 925." "product-trio-pulseiras.jpg"
  create_product "Pulseira Trevo Preto" "169.90" "pulseiras" "Motivos trevo em contraste preto e prata." "product-trevo.jpg"
fi

echo "[AMpratas WP] Pronto."
echo "Loja:  http://localhost:8091"
echo "Admin: http://localhost:8091/wp-admin  (admin / ampratas123)"
