RUN_COMMAND=docker-compose run --rm site1.local

PHPUNIT_FLAGS=

ifdef filter
	PHPUNIT_FLAGS+=--filter=$(filter)
endif

default: init

init: down build up

down:
	docker-compose down

build:
	docker-compose build

up:
	docker-compose up -d

ssh:
	docker exec -it site1-local bash

composer-istall:
	$(RUN_COMMAND) composer install

composer-update:
	$(RUN_COMMAND) composer update

test:
	$(RUN_COMMAND) /application/bin/phpunit -c /application/phpunit.xml.dist --stop-on-failure $(PHPUNIT_FLAGS)

coveralls:
	$(RUN_COMMAND) php /application/bin/php-coveralls -v


run-script:
	docker exec -it \
	site1-local \
	php /application/$(script)

site1:
	open -a "Firefox" http://localhost:8880/

site2:
	open -a "Firefox" http://localhost:8881/


