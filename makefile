.PHONY: cs-fix phpstan pipeline

cs-fix:
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix --verbose --diff

phpstan:
	php ./vendor/bin/phpstan analyse --memory-limit=512M

pipeline: cs-fix phpstan

reset:
	php bin/console doctrine:database:drop --force
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate
	php bin/console doctrine:fixtures:load
