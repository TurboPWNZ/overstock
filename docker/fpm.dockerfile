FROM php:8.4-fpm-alpine

RUN apk update && apk add bash
RUN apk add --no-cache zip libzip-dev
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo pdo_mysql

# use development php ini
RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
# set default timezone
RUN sed -ri -e 's!;date.timezone =!date.timezone = "Europe/Kiev"!g' /usr/local/etc/php/php.ini
RUN echo "always_populate_raw_post_data = -1" >> /usr/local/etc/php/php.ini

WORKDIR /var/www/web

CMD /bin/sh -c "" && \

php-fpm
#CMD /bin/sh -c "cp app/configs/megalot/application.ini app/configs/application.ini" && \
#CMD /bin/sh -c "cp app/configs/megalot/database.ini app/configs/database.ini" && \
##composer install && \
##composer dump-autoload && \