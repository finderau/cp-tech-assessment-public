# WordPress Playground - Troubleshooting Guide

This document contains known issues encountered during setup and their solutions.

## Setup Issues

### 1. Docker Network Conflict

**Error:**
```
network wp-playground was found but has incorrect label com.docker.compose.network set to "" (expected: "wp-playground")
```

**Cause:** The `wp-playground` network was created manually (via `docker network create`) before Docker Compose could create it with proper labels.

**Solution:** Let Docker Compose manage the `wp-playground` network. The Makefile and Taskfile only create external networks (mysql, redis, traefik) that are shared with other services. If you encounter this error:

```bash
# Remove the manually created network
docker network rm wp-playground

# Restart containers (docker-compose will create the network)
make restart
```

---

### 2. Nginx Duplicate Location Error

**Error:**
```
nginx: [emerg] duplicate location "/favicon.ico" in /etc/nginx/templates/wordpress.conf:35
```

**Cause:** The base nginx image already defines a `/favicon.ico` location block. Adding another in `wordpress.conf` causes a conflict.

**Solution:** Remove the duplicate favicon.ico location from `nginx/includes/wordpress.conf`. The base nginx config handles favicon requests.

---

### 3. MySQL Access Denied

**Error:**
```
ERROR 1045 (28000): Access denied for user 'root'@'localhost' (using password: YES)
```

**Cause:** The MySQL container (`coeval-mysql`) may be configured without a root password, but the Makefile and environment files specify `abcd1234`.

**Solution:** 

Option A - If MySQL has no password:
```bash
# Create database without password
docker exec coeval-mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS wp_playground_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Option B - Check actual MySQL configuration:
```bash
# Test connection without password
docker exec coeval-mysql mysql -uroot -e "SELECT 1;"

# If that works, your MySQL has no root password
# Update config/playground.local.env:
echo "DB_PASS=" >> config/playground.local.env
```

Option C - Update Makefile to match your MySQL setup by editing the `DB_PASS` variable.

---

### 4. Docker Compose Version Warning

**Warning:**
```
the attribute `version` is obsolete, it will be ignored, please remove it to avoid potential confusion
```

**Cause:** Docker Compose v2+ no longer requires the `version` attribute in compose files.

**Solution:** This is just a warning and doesn't affect functionality. The `version` attribute has been removed from `docker-compose.yml`.

---

### 5. Bad Gateway (502) Error

**Error:** Browser shows "502 Bad Gateway" when accessing the site.

**Cause:** Nginx cannot connect to PHP-FPM. This typically happens when:
- The nginx config is missing `fastcgi_pass` directive
- The PHP-FPM hostname is incorrect
- The nginx config references files that don't exist in the standard nginx image

**Diagnosis:**
```bash
# Check nginx error logs
docker logs wp-playground-nginx

# Check if PHP-FPM is reachable from nginx
docker exec wp-playground-nginx ping -c 1 php-fpm
```

**Solution:** Ensure the nginx config includes:
```nginx
location ~ \.php$ {
    fastcgi_pass php-fpm:9000;
    include /etc/nginx/fastcgi.conf;
}
```

The hostname `php-fpm` is set via the `links` directive in docker-compose.yml.

---

### 6. Nginx Config Not Loading (Standard Image)

**Symptoms:** Nginx starts but doesn't serve the site correctly.

**Cause:** When using standard `nginx:alpine` image, the main `nginx.conf` only includes `/etc/nginx/conf.d/*.conf` by default, not `/etc/nginx/sites-enabled/`.

**Solution:** Place your nginx config in `/etc/nginx/conf.d/default.conf` instead of `sites-enabled`:

```yaml
# In docker-compose.yml
volumes:
  - ./nginx/nginx-site.conf:/etc/nginx/conf.d/default.conf
```

Also ensure the nginx config doesn't reference files that only exist in custom images (like `/etc/nginx/global.conf`).

---

### 7. PHP Files Download Instead of Rendering

**Symptoms:** Accessing `/wordpress/wp-admin/` or other PHP files triggers a file download instead of showing the page. Response headers show `Content-Type: application/octet-stream`.

**Cause:** A more specific nginx location block for `/wordpress/*.php` is matching but missing `fastcgi_pass` directive, so nginx serves the raw PHP file instead of processing it through PHP-FPM.

**Diagnosis:**
```bash
# Check response headers
curl -sI https://playground.finder.dev/wordpress/wp-admin/index.php

# If Content-Type is application/octet-stream, PHP isn't being processed
```

**Solution:** Ensure all PHP location blocks include both `fastcgi_pass` and `include fastcgi.conf`:

```nginx
# In nginx/includes/wordpress.conf
location ~ ^/wordpress/.*\.php$ {
    try_files $uri =404;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass php-fpm:9000;
    fastcgi_index index.php;
    include /etc/nginx/fastcgi.conf;
}
```

After fixing, reload nginx: `docker exec wp-playground-nginx nginx -s reload`

---

## Runtime Issues

### 8. Containers Not Starting

**Symptoms:** `make status` shows containers in "Restarting" state.

**Diagnosis:**
```bash
# Check container logs
docker logs wp-playground-nginx
docker logs wp-playground-php-fpm
```

**Common causes:**
- Nginx configuration errors (check for duplicate locations, syntax errors)
- Missing mount points (ensure all source directories exist)
- Permission issues on mounted volumes

---

### 9. Site Not Accessible

**Symptoms:** Browser shows "Site can't be reached" or connection refused.

**Checklist:**

1. Check containers are running:
   ```bash
   make status
   ```

2. Verify hosts entry:
   ```bash
   grep playground.finder.dev /etc/hosts
   # Should show: 127.0.0.1 playground.finder.dev (or similar)
   ```

3. Check Traefik is routing:
   ```bash
   docker logs traefik 2>&1 | grep playground
   ```

4. Test directly via curl:
   ```bash
   curl -sk https://playground.finder.dev/status.php
   ```

---

### 10. Database Connection Failed in WordPress

**Symptoms:** WordPress shows "Error establishing a database connection".

**Checklist:**

1. Verify database exists:
   ```bash
   docker exec coeval-mysql mysql -uroot -e "SHOW DATABASES;" | grep wp_playground
   ```

2. Check DB credentials in `config/playground.defaults.env` match your MySQL setup

3. Verify PHP container can reach MySQL:
   ```bash
   docker exec wp-playground-php-fpm ping -c 1 coeval-mysql
   ```

4. Check the `mysql` network exists and containers are connected:
   ```bash
   docker network inspect mysql
   ```

---

### 11. PHP Errors or White Screen

**Diagnosis:**
```bash
# Check PHP-FPM logs
docker logs wp-playground-php-fpm

# Or check log files
tail -f ~/logs/wp-playground/*.log
```

**Common fixes:**
- Enable WP_DEBUG in `config/playground.local.env`:
  ```
  WP_DEBUG=true
  WP_DEBUG_LOG=true
  WP_DEBUG_DISPLAY=true
  ```
- Restart containers after config changes: `make restart`

---

## Volume Mount Issues

### 12. Changes Not Reflecting

**Symptoms:** Code changes in `wordpress-core/src/` don't appear on the site.

**Cause:** Docker volume caching (especially on macOS).

**Solutions:**
- Hard refresh browser (Cmd+Shift+R / Ctrl+Shift+R)
- Restart containers: `make restart`
- Check the mount is correct: `docker exec wp-playground-php-fpm ls -la /var/www/src/wp-content/`

---

### 13. Permission Denied on Uploads

**Symptoms:** Cannot upload files via WordPress admin.

**Solution:**
```bash
# Ensure uploads directory exists and is writable
mkdir -p wordpress-core/src/uploads
chmod 775 wordpress-core/src/uploads

# Restart to apply
make restart
```

---

## Quick Reference

| Issue | Quick Fix |
|-------|-----------|
| Network conflict | `docker network rm wp-playground && make restart` |
| Bad Gateway (502) | Check nginx has `fastcgi_pass php-fpm:9000;` in PHP location |
| Nginx config not loading | Mount to `/etc/nginx/conf.d/default.conf` not `sites-enabled` |
| PHP files download | Add `fastcgi_pass php-fpm:9000;` to WordPress PHP location block |
| Nginx config error | Check logs: `docker logs wp-playground-nginx` |
| DB access denied | Try without password: `docker exec coeval-mysql mysql -uroot -e "..."` |
| Site not loading | Check: `make status`, hosts file, Traefik logs |
| PHP errors | Enable debug: `WP_DEBUG=true` in local.env |
