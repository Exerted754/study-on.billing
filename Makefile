COMPOSE=docker compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer
PHPUNIT=$(PHP) vendor/bin/phpunit

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load

db_test_reset:
	@${CONSOLE} --env=test doctrine:database:drop --if-exists --force
	@${CONSOLE} --env=test doctrine:database:create --if-not-exists
	@${CONSOLE} --env=test doctrine:migrations:migrate -n
	@${CONSOLE} --env=test doctrine:fixtures:load -n

phpunit:
	@${PHPUNIT}

# В файл local.mk можно добавлять дополнительные make-команды,
# которые требуются лично вам, но не нужны на проекте в целом
-include local.mk
