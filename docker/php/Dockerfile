ARG COMPOSE_ENV_PHP_VERSION
FROM php:${COMPOSE_ENV_PHP_VERSION}-fpm

ARG BUILD_NPROC
ARG BUILD_PERLPROC
ARG EXT_XDEBUG_BRANCH=xdebug_2_7

ARG COMPOSE_ENV_PHP_VERSION
ENV COMPOSE_ENV_PHP_VERSION=$COMPOSE_ENV_PHP_VERSION
ARG GOLANG_VERSION
ENV GOLANG_VERSION=$GOLANG_VERSION
ARG APP_LIBRARY_PERL_REPO
ENV APP_LIBRARY_PERL_REPO=$APP_LIBRARY_PERL_REPO
ENV GOPATH /go
ENV GOCACHE $GOPATH/.cache
ENV PATH $GOPATH/bin:/usr/local/go/bin:$PATH

ARG APPLICATION_ENV='production'
ENV APPLICATION_ENV=$APPLICATION_ENV
ENV WAIT_HOSTS "mariadb:3306"
ENV WAIT_HOSTS_TIMEOUT "60"
ENV WAIT_AFTER_HOSTS "1"
ENV DB_SEEDING_ENABLED "0"
ENV DB_DATABASE_TESTING "infocmdb_testing"

ARG DOCKER_TIMEZONE='Europe/Vienna'
ENV TZ "${DOCKER_TIMEZONE}"

SHELL ["bash", "-c"]

ADD ./docker/php/  /bootstrap
ADD ./docker/cron/ /bootstrap/cron
ADD ./docker/php/perl/cpanfile* /tmp/
ADD ./docker/php/perl/cpanm /bin/cpanm
ADD ./docker/php/perl/cpm /bin/cpm
ADD ./docker/php/logrotate.d  /etc/
ADD . /app/

RUN echo -e '\e[44mImageVersion: ${COMPOSE_ENV_PHP_VERSION}\e[0m' && \
    echo -e '\e[44mEnvironment: ${APPLICATION_ENV}\e[0m' && \
    if [ "${APPLICATION_ENV}" != "production" ] ; then \
        echo -e '\e[42mBuild dev environment\e[0m' ; \
    else \
        echo -e '\e[43mBuild prod environment\e[0m' ; \
    fi

RUN set -e \
    && rm -rf /app/application/configs /app/public/_uploads /app/library/perl /app/library/golang \
    && echo -e '\e[44mInstall linux system packages\e[0m' \
    && apt-get update \
    && apt-get -u  -y --no-install-recommends dist-upgrade \
    && apt-get install -y --no-install-recommends \
        build-essential \
        dos2unix \
        git \
        ssh \
        libdbi-perl \
        libdbd-mysql-perl \
        libdbd-sybase-perl \
        libc-client-dev \
        libexpat1-dev \
        libfcgi-bin \
        libfcgi0ldbl \
        libldap2-dev \
        libldb-dev \
        libmariadbd-dev \
        libssh2-1-dev \
        libkrb5-dev \
        libxml2-dev \
        libzip-dev \
        locales \
        logrotate \
        mariadb-client \
        procps \
        smbclient \
        sudo \
        unzip \
        zip \
        unzip \
        zlib1g-dev \
        && \
        if [ "${APPLICATION_ENV}" = "development" ] ; then \
            apt-get install -y --no-install-recommends \
                dnsutils \
                less \
                nano \
                nmap \
                telnet \
                traceroute \
                vim \
                wget \
        ; fi \
    \
    && sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && locale-gen \
    \
    && echo -e '\e[44mInstall php extensions\e[0m' \
    && git clone -b ${EXT_XDEBUG_BRANCH} --depth 1 https://github.com/xdebug/xdebug.git /usr/src/php/ext/xdebug \
    && cd /usr/src/php/ext/xdebug && git submodule update --init \
    && phpize && ./configure && make -j${BUILD_NPROC} && make install \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j${BUILD_NPROC} \
        soap \
        pdo_mysql \
        bcmath \
        imap \
        ldap \
        opcache \
        zip \
    \
    && echo -e '\e[44mInstall perl modules\e[0m' \
    && chmod +x /bin/cpanm /bin/cpm \
    && /bin/cpm install -g --without-test --workers=${BUILD_PERLPROC} --cpanfile=/tmp/cpanfile_base \
    && /bin/cpm install -g --without-test --workers=${BUILD_PERLPROC} --cpanfile=/tmp/cpanfile_infocmdb_lib \
    && rm -rf /root/.perl-cpm \
    \
    && echo -e '\e[44mConfigure linux system\e[0m' \
    && dos2unix /usr/local/bin/docker-php-entrypoint \
    && echo "alias 'php'=\"sudo -u www-data php\"" >> /root/.bashrc \
    && echo "alias 'composer'=\"sudo -u www-data /app/composer.phar\"" >> /root/.bashrc \
    \
    && chown -R www-data:www-data /app/public \
    && echo -e '\e[44mRun composer commands\e[0m' \
    && ( \
        cd /app \
        && chmod +x /app/composer.phar \
        && rm -rf /tmp/composer /app/public/js/utils/util.min.js \
        && su www-data -s /bin/sh -c "/app/composer.phar run-script minify" \
    ) \
    \
    && echo -e '\e[44mInstall Golang\e[0m' \
    && ( \
        url="https://golang.org/dl/go${GOLANG_VERSION}.linux-amd64.tar.gz" \
        && curl -s -L "$url" -o /tmp/go.tgz \
        && tar -C /usr/local -xzf /tmp/go.tgz \
        && export PATH="/usr/local/go/bin:$PATH" \
        && go version \
        ) \
    && mkdir -p "$GOPATH/src" "$GOPATH/bin" "$GOCACHE" && chmod -R 777 "$GOPATH" "$GOCACHE" \
    && echo -e '\e[44mInstall Library PERL\e[0m' \
    && rm -rf /app/_dist/data/library/perl \
    && git clone --depth 1 ${APP_LIBRARY_PERL_REPO} /app/_dist/data/library/perl \
    \
    && echo -e '\e[44mFIX Permissions\e[0m' \
    && chown -R www-data:www-data /app "/$GOPATH" \
    \
    && echo -e '\e[44mCleanup\e[0m' \
    && apt-get auto-remove \
    && ( find /app -name ".gitkeep" -type f -delete || true) \
    && ( find /app -name ".git" -type d | xargs rm -r ) \
    && go clean -cache \
    && apt-get clean && rm -rf /var/lib/apt/lists/* && rm -rf /tmp/* /usr/src/*

ENTRYPOINT ["/bootstrap/init.sh", "docker-php-entrypoint"]
HEALTHCHECK --interval=20s --timeout=3s --retries=3 --start-period=30s CMD [ "/bootstrap/check_health.sh" ]

WORKDIR /app
