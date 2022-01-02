default:
	echo "No default command";

build-dev:
	composer install
	make download-phpunit

# Dev-env
run:
	docker-compose up
	# docker run --name wp-collections -p 8080:80 -d --rm wordpress

shell:
	docker-compose exec wordpress /bin/bash

# Tests
test:
	./vendor/bin/phpunit

test-in-docker:
	docker-compose exec -w /var/www/html/ wordpress make test

download-wp-tests-lib:
	rm -rf ./custom/wp-tests-lib
	svn co https://develop.svn.wordpress.org/tags/5.8.2/tests/phpunit/includes ./custom/wp-tests-lib/includes
	svn co https://develop.svn.wordpress.org/tags/5.8.2/tests/phpunit/data     ./custom/wp-tests-lib/data
	svn co https://develop.svn.wordpress.org/tags/5.8.2/tests/phpunit/tests    ./custom/wp-tests-lib/tests

download-wp-core:
	rm -rf ./custom/wp-core
	git clone https://github.com/johnpbloch/wordpress-core.git ./custom/wp-core/ --branch 5.8.2 --single-branch
