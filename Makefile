up:
	docker-compose --env-file .env.local up -d

stop:
	docker-compose stop

down:
	docker-compose down

migration:
	docker-compose exec php bin/console make:migration

migrate:
	docker-compose exec php bin/console doctrine:migrations:migrate

test:
	docker-compose exec php ./vendor/bin/phpunit

phpcs:
	php ./vendor/bin/phpcs

phpcbf:
	php ./vendor/bin/phpcbf

pcs-fixer-dry:
	php ./vendor/bin/php-cs-fixer fix --dry-run --diff

pcs-fixer-fix:
	php ./vendor/bin/php-cs-fixer fix

psalm:
	php ./vendor/bin/psalm

.PHONY: up
.PHONY: stop
.PHONY: down
.PHONY: migration
.PHONY: migrate
.PHONY: test
.PHONY: phpcs
.PHONY: phpcbf
.PHONY: pcs-fixer-dry
.PHONY: pcs-fixer-fix
.PHONY: psalm