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

test:
	docker-compose exec \
	site1.local \
	/application/bin/phpunit -c /application/phpunit.xml.dist --stop-on-failure $(PHPUNIT_FLAGS)

run-script:
	docker exec -it \
	site1-local \
	php /application/$(script)

site1:
	open -a "Firefox" http://localhost:8880/

site2:
	open -a "Firefox" http://localhost:8881/


