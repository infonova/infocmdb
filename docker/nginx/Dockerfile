ARG COMPOSE_ENV_NGINX_VERSION
FROM nginx:${COMPOSE_ENV_NGINX_VERSION}

ARG APPLICATION_ENV='production'
ENV APPLICATION_ENV=$APPLICATION_ENV
ENV WAIT_HOSTS "php:9000"
ENV WAIT_HOSTS_TIMEOUT "120"
ENV WAIT_SLEEP_INTERVAL "5"

ADD ./docker/nginx/ /bootstrap
ADD ./public/ /app/public

ENTRYPOINT [ "/bootstrap/init.sh" , "nginx" , "-g" , "daemon off;" ]