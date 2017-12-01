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
	docker exec -it \
	site1-local \
	/application/bin/phpunit -c /application/phpunit.xml.dist $(PHPUNIT_FLAGS)


