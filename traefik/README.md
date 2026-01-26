# Traefik SSL Certificate Setup

This directory contains Traefik configuration for the standalone WordPress Playground setup.

## Generating Self-Signed SSL Certificates

Before running the standalone setup, you need to generate SSL certificates for `playground.finder.dev`.

### Option 1: Using mkcert (Recommended)

[mkcert](https://github.com/FiloSottile/mkcert) creates locally-trusted certificates automatically.

```bash
# Install mkcert (macOS)
brew install mkcert

# Install the local CA (one-time setup)
mkcert -install

# Generate certificates for playground.finder.dev
cd traefik/ssl
mkcert playground.finder.dev

# Rename to expected filenames
mv playground.finder.dev.pem playground.crt
mv playground.finder.dev-key.pem playground.key
```

### Option 2: Using OpenSSL

```bash
# Create ssl directory
mkdir -p traefik/ssl
cd traefik/ssl

# Generate private key
openssl genrsa -out playground.key 2048

# Generate certificate signing request
openssl req -new -key playground.key -out playground.csr \
  -subj "/C=AU/ST=NSW/L=Sydney/O=Finder/CN=playground.finder.dev"

# Generate self-signed certificate (valid for 365 days)
openssl x509 -req -days 365 -in playground.csr \
  -signkey playground.key -out playground.crt \
  -extfile <(printf "subjectAltName=DNS:playground.finder.dev,DNS:*.playground.finder.dev")

# Cleanup CSR
rm playground.csr
```

**Note:** With OpenSSL-generated certificates, your browser will show a security warning. You'll need to manually accept the certificate or add it to your system's trusted certificates.

## Adding to /etc/hosts

Add the following entry to your `/etc/hosts` file:

```
127.0.0.1 playground.finder.dev
```

## Directory Structure

After setup, your `traefik/` directory should look like:

```
traefik/
├── traefik.toml      # Main Traefik configuration
├── dynamic.toml      # TLS certificate configuration
├── README.md         # This file
└── ssl/
    ├── playground.crt
    └── playground.key
```

## Accessing Services

Once running:

- **WordPress Playground**: https://playground.finder.dev
- **Traefik Dashboard**: http://localhost:8080

## Troubleshooting

### Certificate not trusted
If using OpenSSL, you may need to:
1. Accept the certificate warning in your browser
2. Or import the certificate into your system keychain

### Connection refused
1. Ensure all containers are running: `docker compose -f docker-compose-standalone.yml ps`
2. Check Traefik logs: `docker logs wp-playground-traefik`
3. Verify `/etc/hosts` entry exists
