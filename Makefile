pint-fix:
	docker compose exec app ./vendor/bin/pint --verbose

migrate:
	docker compose exec app php artisan migrate --force

test:
	docker compose exec app php artisan test

rates-update:
	docker compose exec app php artisan currency:update-rates
