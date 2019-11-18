ARG COMPOSE_ENV_DB_VERSION
FROM mariadb:${COMPOSE_ENV_DB_VERSION}

ARG DOCKER_TIMEZONE='Europe/Vienna'
ENV TZ "${DOCKER_TIMEZONE}"

ADD ./docker/mariadb /bootstrap

EXPOSE 3306

HEALTHCHECK --interval=20s --timeout=5s --retries=3 --start-period=40s \
    CMD [ "sh", "-c", "/bootstrap/check_health.sh" ]

ENTRYPOINT [ "/bootstrap/init.sh", "docker-entrypoint.sh", "mysqld" ]