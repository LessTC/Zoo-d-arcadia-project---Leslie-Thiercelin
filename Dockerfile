# Image officielle PHP avec Apache, dans la version de la production.
FROM php:8.4-apache

# pdo_mysql est fourni avec PHP ; l'extension MongoDB doit être compilée.
# php.net signale que l'échec le plus courant vient d'une bibliothèque SSL
# introuvable : d'où libssl-dev et pkg-config.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libssl-dev pkg-config \
 && pecl install mongodb \
 && docker-php-ext-enable mongodb \
 && docker-php-ext-install pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Sans ces deux lignes, Apache ignore le fichier .htaccess du projet et les
# dossiers .git, config et sql redeviennent téléchargeables.
RUN a2enmod rewrite \
 && printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n' \
      > /etc/apache2/conf-available/arcadia.conf \
 && a2enconf arcadia