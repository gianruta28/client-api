#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ]; then
    # Composer install on service run
	if [ "$APP_ENV" != 'prod' ]; then
		composer install --prefer-dist --no-interaction --optimize-autoloader

    # Deleting and Creating db for insertion of data every time the container starts.
    # Comment section to avoid this behavior
    # Droping actual db and creating a new one
    php bin/console doc:database:drop --force
    php bin/console doc:database:create
    php bin/console --no-interaction doctrine:migrations:migrate
    php bin/console  doctrine:fixtures:load --no-interaction


	fi

	php bin/console lexik:jwt:generate-keypair


    # Specifies that nc should only scan for listening daemons
    # without sending any data to them.
    until nc -z ${MYSQL_HOST} ${MYSQL_PORT}; do
        echo "*** Database connection attempt ***"
        sleep 3
    done
fi



exec docker-php-entrypoint "$@"