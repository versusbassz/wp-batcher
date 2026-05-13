default:
	echo "No default command";

build-dev:
	composer install
	make download-wp-core
	make download-wp-tests-lib

# Dev-env
up:
	docker compose up -d
	# docker run --name wp-collections -p 8080:80 -d --rm wordpress

start:
	docker compose start

stop:
	docker compose stop

prune:
	docker compose down -v

shell:
	docker compose exec -w /var/www/html/ wordpress /bin/bash

# Tests
test:
	./vendor/bin/phpunit

test-in-docker:
	docker compose exec -w /var/www/html/ wordpress make test

download-wp-tests-lib:
	mkdir -p ./custom
	rm -rf ./custom/wp-tests-lib
	rm -rf ./custom/wp-tests-lib-tmp
	git clone --depth 1 --branch 6.9.4 https://github.com/WordPress/wordpress-develop.git ./custom/wp-tests-lib-tmp/
	mkdir -p ./custom/wp-tests-lib
	mv ./custom/wp-tests-lib-tmp/tests/phpunit/data     ./custom/wp-tests-lib/data
	mv ./custom/wp-tests-lib-tmp/tests/phpunit/includes ./custom/wp-tests-lib/includes
	mv ./custom/wp-tests-lib-tmp/tests/phpunit/tests    ./custom/wp-tests-lib/tests
	rm -rf ./custom/wp-tests-lib-tmp

download-wp-core:
	mkdir -p ./custom
	rm -rf ./custom/wp-core
	git clone --depth 1 --branch 6.9.4 https://github.com/johnpbloch/wordpress-core.git ./custom/wp-core/
