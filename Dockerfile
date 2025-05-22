FROM php:8.2-apache

ARG DOCKER_GID

RUN docker-php-ext-install sockets mysqli

COPY apacherewritepermissions.conf /etc/apache2/conf-available/rewrite-permissions.conf

RUN a2enmod rewrite && \
    a2enconf rewrite-permissions

RUN if [ -z "${DOCKER_GID}" ]; then \
        echo "Error: DOCKER_GID not provided. Aborting." >&2; \
        exit 1; \
    else \
        DOCKER_GID_TO_USE=${DOCKER_GID}; \
    fi && \
    echo "Creating docker group with GID: ${DOCKER_GID_TO_USE}" && \
    groupadd -g ${DOCKER_GID_TO_USE} docker || echo "Group docker with GID ${DOCKER_GID_TO_USE} may already exist. Continuing."

RUN usermod -aG docker www-data

RUN echo "www-data groups:" && id www-data

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

COPY apacherewritepermissions.conf /etc/apache2/conf-available/rewrite-permissions.conf

EXPOSE 80