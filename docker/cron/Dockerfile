FROM debian:stable-slim

ADD ./docker/cron/* /bootstrap/
ADD ./docker/cron/docker-entrypoint /
ARG DEBIAN_FRONTEND=noninteractive

ARG APPLICATION_ENV='production'
ENV APPLICATION_ENV=$APPLICATION_ENV
ENV WAIT_HOSTS "php:9000"
ENV WAIT_HOSTS_TIMEOUT "120"
ENV WAIT_SLEEP_INTERVAL "5"

RUN apt-get update \
        && apt-get install -y --no-install-recommends libfcgi-bin procps \
        && chmod +x docker-entrypoint \
        && rm -rf /var/lib/cache

HEALTHCHECK --interval=5s --timeout=3s --retries=3 --start-period=120s \
    CMD ps aux | grep 'infocmdb_cron' || exit 1

CMD ["sh", "-c", "/bootstrap/infocmdb_cron.sh" ]
ENTRYPOINT ["/docker-entrypoint"]
