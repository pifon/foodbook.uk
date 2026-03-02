#!/bin/sh
# Create self-signed cert for https://foodbook (local). Run once; RCP-API nginx must have /etc/letsencrypt mounted.
set -e
CERT_DIR="/etc/letsencrypt/live/foodbook"
if [ -f "$CERT_DIR/fullchain.pem" ] && [ -f "$CERT_DIR/privkey.pem" ]; then
  echo "Cert for foodbook already exists at $CERT_DIR"
  exit 0
fi
echo "Creating self-signed cert for foodbook in $CERT_DIR (needs sudo)"
sudo mkdir -p "$CERT_DIR"
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout "$CERT_DIR/privkey.pem" \
  -out "$CERT_DIR/fullchain.pem" \
  -subj "/CN=foodbook" \
  -addext "subjectAltName=DNS:foodbook,DNS:localhost"
echo "Done. Reload RCP-API nginx (e.g. docker exec api nginx -s reload) and open https://foodbook"
