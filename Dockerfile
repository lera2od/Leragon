FROM php:8.2-apache

ARG DOCKER_GID

RUN docker-php-ext-install sockets

RUN a2enmod rewrite

RUN if [ -z "${DOCKER_GID}" ]; then \
        echo "Warning: DOCKER_GID not provided, using default 999. This might not match host GID."; \
        DOCKER_GID_TO_USE=999; \
    else \
        DOCKER_GID_TO_USE=${DOCKER_GID}; \
    fi && \
    echo "Creating docker group with GID: ${DOCKER_GID_TO_USE}" && \
    groupadd -g ${DOCKER_GID_TO_USE} docker || echo "Group docker with GID ${DOCKER_GID_TO_USE} may already exist. Continuing."

RUN usermod -aG docker www-data

RUN echo "www-data groups:" && id www-data

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
