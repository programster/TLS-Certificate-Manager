FROM debian:12


ENV DEBIAN_FRONTEND=noninteract


RUN apt-get update && apt-get dist-upgrade -y
RUN adduser admin


# Expose port 80 for the web requests
EXPOSE 80


# Install the relevant packages
RUN apt-get update && apt-get install vim apache2 curl libapache2-mod-php8.2 git unzip cron composer \
    php8.2-cli php8.2-xml php8.2-mbstring php8.2-curl php8.2-bcmath php8.2-yaml \
    php8.2-zip -y


# Enable the php mod we just installed
RUN a2enmod php8.2 && a2enmod rewrite



# Manually set the apache environment variables in order to get apache to work immediately.
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_RUN_DIR=/var/run/apache2

# It appears that the new apache requires these env vars as well
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2/apache2.pid


# Set display errors to on, we can decide in the application layer whether to hide or not based on the environment.
# Also bump up limits like the maximum
# upload size and memory limits, as we want to allow admins to upload massive hoard files and download large
# generated PDFs.
RUN sed -i 's;display_errors = .*;display_errors = On;' /etc/php/8.2/apache2/php.ini && \
    sed -i 's;post_max_size = .*;post_max_size = 100M;' /etc/php/8.2/apache2/php.ini && \
    sed -i 's;upload_max_filesize = .*;upload_max_filesize = 100M;' /etc/php/8.2/apache2/php.ini


# Add the site's code to the container.
# When in development, use a volume to overwrite this area.
COPY --chown=root:www-data site /var/www/site


# Install PHP packages
RUN chmod 750 --recursive /var/www/site \
    && cd /var/www/site \
    && composer install \
    && chown --recursive root:www-data /var/www/site/vendor \
    && chmod 750 --recursive /var/www/site/vendor


# Update our apache sites available with the config we created
ADD docker/apache-config.conf /etc/apache2/sites-enabled/000-default.conf


# Add the ssl configuration in case will be enabled on startup.
ADD docker/apache-ssl-config.conf /etc/apache2/sites-available/default-ssl.conf


# Use the crontab file.
# The crontab file was already added when we added "project"
ADD docker/crons.conf /root/crons.conf
RUN crontab /root/crons.conf && rm /root/crons.conf


# Copy the script across that will create the .env file on startup for the web user to use.
COPY docker/create-env-file.php /root/create-env-file.php


# Add the startup script to the container
COPY docker/startup.sh /root/startup.sh


# Change the workdir to /var/www/site so that when devs enter the container, they are at the site
WORKDIR /var/www/site


# Execute the containers startup script which will start many processes/services
# The startup file was already added when we added "project"
CMD ["/bin/bash", "/root/startup.sh"]
