# Please do not manually call this file!
# This script is run by the docker container when it is "run"


# Create the .env file
php /root/create-env-file.php /.env


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
    RUN a2enmod ssl
    RUN a2ensite default-ssl
fi



if [ -n "$MOUNTED_SITE" ]; then
  echo "Mounted site into container, so setting permissions..."
  bash /var/www/site/scripts/remap-user.sh
  chown admin:www-data --recursive /var/www/site
  chmod 750 --recursive /var/www/site
  echo "done."
fi


# Run the apache process in the background
/usr/sbin/apache2 -D APACHE_PROCESS &


# Start the cron service in the foreground
# We dont run apache in the FG, so that we can restart apache without container
# exiting.
cron -f
