# Laravel Storage Permission Fix

This project uses Docker named volumes for Laravel writable directories:

- `/var/www/html/storage`
- `/var/www/html/bootstrap/cache`

The app container runs `docker/php/entrypoint.sh` before PHP-FPM starts. The script creates Laravel storage folders, touches `storage/logs/laravel.log`, and makes the folders writable for local Docker development.

If you previously ran an older compose setup, recreate the containers and volumes:

```bash
docker compose down -v --remove-orphans
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```
