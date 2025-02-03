.PHONY: cs-fix phpstan pipeline

cs-fix:
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix --verbose --diff

phpstan:
	php ./vendor/bin/phpstan analyse

pipeline: cs-fix phpstan
