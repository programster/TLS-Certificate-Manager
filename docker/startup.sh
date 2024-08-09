#!/bin/bash

# Please do not manually call this file!
# This script is run by the docker container when it is "run"

set -e


if [ -n "$MOUNTED_SITE" ]; then
  echo "Mounted site into container, so setting permissions..."
  bash /var/www/site/scripts/remap-user.sh
  chown admin:www-data --recursive /var/www/site
  chmod 750 --recursive /var/www/site
  echo "done."
fi


# Create the .env file
php /root/create-env-file.php /.env
chmod 750 /.env
chown root:www-data /.env

if [ $SSL_ENABLED == "1" ]; then

    # create TLS certificates if none were provided
    FILE="/etc/apache2/ssl/fullchain.pem"

    if ! [ -f $FILE ]; then
        echo "SSL/TLS certificates were not provided, creating some self-signed ones!"

        openssl req \
           -newkey rsa:2048 \
           -nodes -keyout /etc/apache2/ssl/private.pem \
           -x509 \
           -days 365 \
           -subj "/C=GB/ST=Acme/L=Acme/O=Acme/OU=IT Department/CN=localhost/emailAddress=support@acme.org" \
           -out /etc/apache2/ssl/fullchain.pem
    fi

    a2enmod rewrite
    a2enmod ssl
    a2ensite default-ssl
fi


# Run migrations after waiting for the database to be available.
php /var/www/site/scripts/wait-for-database.php
php /var/www/site/scripts/migrate.php


# Run the apache process in the background
/usr/sbin/apache2 -D APACHE_PROCESS &


# Start the cron service in the foreground
# We dont run apache in the FG, so that we can restart apache without container
# exiting.
cron -f
