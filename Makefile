default:
	echo "No default command";

build-dev:
	composer install
	make download-phpunit

# Dev-env
run:
	docker-compose up
	# docker run --name wp-collections -p 8080:80 -d --rm wordpress

# Tests
test:
	./vendor/bin/phpunit --color=auto --bootstrap="tests/bootstrap.php" tests/*

download-phpunit:
	mkdir -p ./bin
	rm -rf ./bin/phpunit
	wget -O ./bin/phpunit https://phar.phpunit.de/phpunit-9.phar
	chmod +x ./bin/phpunit
