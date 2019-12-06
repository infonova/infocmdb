#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob

# usage: file_env VAR [DEFAULT]
#    ie: file_env 'XYZ_DB_PASSWORD' 'example'
# (will allow for "$XYZ_DB_PASSWORD_FILE" to fill in the value of
#  "$XYZ_DB_PASSWORD" from a file, especially for Docker's secrets feature)
file_env() {
	local var="$1"
	local fileVar="${var}_FILE"
	local def="${2:-}"
	if [ "${!var:-}" ] && [ "${!fileVar:-}" ]; then
		echo >&2 "error: both $var and $fileVar are set (but are exclusive)"
		exit 1
	fi
	local val="$def"
	if [ "${!var:-}" ]; then
		val="${!var}"
	elif [ "${!fileVar:-}" ]; then
		val="$(< "${!fileVar}")"
	fi
	export "$var"="$val"
	unset "$fileVar"
}

file_env 'DB_HOST'
file_env 'DB_PORT'
file_env 'DB_DATABASE'
file_env 'DB_DATABASE_TESTING'
file_env 'DB_USERNAME'
file_env 'DB_PASSWORD'
file_env 'DB_ROOT_PASSWORD'

if [[ ${DB_ROOT_PASSWORD} == "" || ${DB_PASSWORD} == "" ]]; then
    echo "DB Passwords not set!"
    exit 1
fi

ERRORS=0
if [[ -e /app/library/perl && ! -L /app/library/perl ]]; then
    echo "/app/library/perl exists as a regular directory should be a symlink"
    ERRORS=1
fi

if [[ -e /app/library/golang && ! -L /app/library/golang ]]; then
    echo "/app/library/golang exists as a regular directory should be a symlink"
    ERRORS=1
fi

if [[ -e /app/application/configs && ! -L /app/application/configs ]]; then
    echo "/app/application/configs exists as a regular directory should be a symlink"
    ERRORS=1
fi
if [[ -e /app/public/_uploads && ! -L /app/public/_uploads ]]; then
    echo "/app/public/_uploads exists as a regular directory should be a symlink"
    ERRORS=1
fi


if [[ ${ERRORS} != 0 ]]; then
    echo "

Directories need to be moved for the new structure:

    /app/library/golang      -->  /data/library/golang
    /app/library/perl        -->  /data/library/perl
    /app/application/configs -->  /data/configs
    /app/public/_uploads     -->  /data/uploads

Please fix and rerun.
"
    exit 1
fi

ln -sT ../data/library/golang   /app/library/golang       || true
ln -sT ../data/library/perl     /app/library/perl         || true
ln -sT ../data/configs          /app/application/configs  || true
ln -sT ../data/uploads          /app/public/_uploads      || true

echo "initialize with /_dist files"

change_www_data_userid=${APP_WWW_DATA_USERID:-}

if [[ "${change_www_data_userid}" != "" ]]; then
  echo "Changing /app and /bootstrap permissions to belong to user id ${change_www_data_userid}"
  usermod -u ${change_www_data_userid} www-data
  groupmod -g ${change_www_data_userid} www-data

  chown -R www-data:www-data /app /bootstrap
fi

chown -c www-data:www-data /app /app/data
find /app/_dist /app/data -type d -not -user www-data -exec sh -c 'chmod -c 0775 "$1"; chown -c www-data:www-data "$1"' _ {} \;

su www-data -s /bin/sh -c "chown -R www-data:www-data /app/_dist"
su www-data -s /bin/sh -c "cp -nr /app/_dist/* /app/"
su www-data -s /bin/sh -c "cp -rf /app/_dist/data/languages/* /app/data/languages/"

echo "Copy default config for php"
CONFIG_DIR=/usr/local/etc/php/conf.d/
cp -avr /bootstrap/default-conf/* ${CONFIG_DIR}

if [[ -n "$(ls /bootstrap/custom-conf/)" ]]; then
    echo "Copy custom config"
    cp -avr /bootstrap/custom-conf/* ${CONFIG_DIR}
fi

echo "Copy lograted.d files for php"
CONFIG_DIR=/etc/logrotate.d/
cp -avr /bootstrap/logrotate.d/* ${CONFIG_DIR}

echo "init infoCMDB"
echo "... clean cache"
find /app/data/tmp -type f -delete
find /app/data/cache -type f -delete

# write database.ini
echo "
[production]
database.adapter         = pdo_mysql
database.params.host     = ${DB_HOST}
database.params.port     = ${DB_PORT}
database.params.username = ${DB_USERNAME}
database.params.password = ${DB_PASSWORD}
database.params.root_username = ${DB_ROOT_USERNAME}
database.params.root_password = ${DB_ROOT_PASSWORD}
database.params.dbname   = ${DB_DATABASE}
database.params.charset	 = utf8

database.params.profiler.enabled = false
database.params.profiler.class = \"Plugin_Db_Profiler\"

database.cache.dir		 = APPLICATION_DATA \"/cache/metadata/\"
database.console.cache.dir	 = APPLICATION_DATA \"/cache/metadata/console/\"
database.cache.apc       = false

[staging : production]

[development : production]

" > /app/application/configs/database.ini

echo "
[client]
host=${DB_HOST}
port=${DB_PORT}
user="root"
password="${DB_ROOT_PASSWORD}"
" > /root/.my.cnf

if [[ -f /app/deploy/phinx.yml ]]; then
    mv /app/deploy/phinx.yml /app/deploy/phinx.yml.bak
fi

# wait for db and start db setup
if ! /bootstrap/wait; then
    echo "DB Connection not ready please verify setup!"
    exit 1
fi

INFOCMDB_USER_ID=$(mysql -se "select ifnull(max(User), 0) from mysql.user where User = '${DB_USERNAME}';" | cut -f1)
if [ "${INFOCMDB_USER_ID}" = 0 ] ; then

    echo "Creating InfoCMDB Database user: '${DB_USERNAME}'."
    mysql -e "CREATE USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';"
    echo "InfoCMDB Database user created!"
fi

echo "verifying DB-User privileges!"
(
    echo "Setting User-Permissions..."
    mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, SHOW VIEW ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'
            WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;"
    mysql -e "FLUSH PRIVILEGES;"
)

if ! (cd /app/deploy && sh ./phinx migrate -v); then
    echo "migration failed!"
    exit 1
fi

if ! [[ "${APPLICATION_ENV}" == "production" ]]; then

    if [[ "${DB_SEEDING_ENABLED}" -eq "1"  ]]; then
        DB_IS_SEEDED=`mysql -s -N -e "select count(*)>1 from ${DB_DATABASE}.theme"`
        if [[ "$DB_IS_SEEDED" == "0" ]]; then
            echo "[NOTICE] DB-Seeding started!"
            cd /app/deploy && sh ./phinx seed:run
        else
            echo "[NOTICE] DB-Seeding: looks already seeded or used!"
        fi
    fi

    echo -e '\e[44mEnabling xdebug\e[0m'
    docker-php-ext-enable xdebug

    echo "[testing : production]
database.params.dbname   = ${DB_DATABASE_TESTING}
" >> /app/application/configs/database.ini

    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE_TESTING}\`"
    mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON \`${DB_DATABASE_TESTING}\`.* TO '${DB_USERNAME}'@'%'
            WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;"
    mysql -e "FLUSH PRIVILEGES"

    echo "
modules:
    config:
        WebDriver:
            host: hub
        Db:
            dsn: 'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE_TESTING}'
            user: '${DB_USERNAME}'
            password: '${DB_PASSWORD}'
            root_username: '${DB_ROOT_USERNAME}'
            root_password: '${DB_ROOT_PASSWORD}'
" > /app/codeception.yml
fi


API_USER_ID=$(mysql ${DB_DATABASE} -se "select ifnull(max(id), 0) from user where username = 'ext_webservice';" | cut -f1)
if [[ "${API_USER_ID}" == "0" ]] ; then
    API_PASSWORD=$(date +%s | sha256sum | base64 | head -c 40 ; echo)

    echo "creating the 'webservice' theme"
    API_THEME=$(mysql ${DB_DATABASE} -se "INSERT INTO theme (name, description, note, menu_id)
      VALUES ('webservice', 'Webservices', 'Used for API Accounts only needing access to the api(v2)', 0); select last_insert_id()" | cut -f1)

    # add CQL permissions
    mysql ${DB_DATABASE} -se "INSERT INTO theme_privilege (resource_id, theme_id)
    VALUES
    (101, ${API_THEME}),
(102, ${API_THEME}), (103, ${API_THEME}), (104, ${API_THEME}), (105, ${API_THEME}), (106, ${API_THEME}), (107, ${API_THEME}),
(108, ${API_THEME}), (109, ${API_THEME}), (110, ${API_THEME}), (111, ${API_THEME}), (301, ${API_THEME}), (302, ${API_THEME}),
(303, ${API_THEME}), (304, ${API_THEME}), (305, ${API_THEME}), (306, ${API_THEME}), (307, ${API_THEME}), (308, ${API_THEME}),
(309, ${API_THEME}), (310, ${API_THEME}), (311, ${API_THEME}), (401, ${API_THEME}), (402, ${API_THEME}), (403, ${API_THEME}),
(404, ${API_THEME}), (405, ${API_THEME}), (406, ${API_THEME}), (601, ${API_THEME}), (901, ${API_THEME}), (1001, ${API_THEME}),
(1003, ${API_THEME}),(1101, ${API_THEME}),(1102, ${API_THEME}),(1104, ${API_THEME}),(1901, ${API_THEME}),(1902, ${API_THEME}),
(1903, ${API_THEME}),(1904, ${API_THEME}),(1905, ${API_THEME}),(2001, ${API_THEME}),(2002, ${API_THEME}),(2003, ${API_THEME}),
(2004, ${API_THEME}),(2801, ${API_THEME}),(2802, ${API_THEME}),(2803, ${API_THEME}),(2804, ${API_THEME}),(3001, ${API_THEME}),
(3002, ${API_THEME}),(3003, ${API_THEME}),(3004, ${API_THEME}),(4101, ${API_THEME}),(4102, ${API_THEME}),(4104, ${API_THEME})"

    echo "creating the 'webservice' default user"
    mysql ${DB_DATABASE} -se "INSERT INTO user \
    (username, password, email, firstname, lastname, description, note, theme_id) \
    VALUES('ext_webservice', '${API_PASSWORD}', 'infoCMDB-team@bearingpoint.com', 'External', 'Webservice', 'Webservice User', '', ${API_THEME})"

    # write infocmdb lib config
    echo "writing the workflow config for the webservice access"
    echo "
apiUrl: http://web/
apiUser: ext_webservice
apiPassword: ${API_PASSWORD}
autoHistoryHandling: 1
CmdbBasePath: /app/
" > /app/application/configs/workflows/infocmdb.yml
fi

ln -s /app/application/configs/workflows/infocmdb.yml /app/library/perl/etc/infocmdb.yml &>/dev/null || true
# This is to support legacy installation
# TODO: Remove once installations have been migrated to docker
mkdir -p /opt/infoCMDB && ln -sf /app/library/perl /opt/infoCMDB/

if [[ "${change_www_data_userid}" == "" ]]; then
    chmod -R 0775 /bootstrap /usr/local/etc/php
    chown -R root. /bootstrap /usr/local/etc/php
fi

CLEAN_ENV="DB_HOST DB_PORT DB_USERNAME DB_PASSWORD DB_ROOT_USERNAME DB_ROOT_PASSWORD DB_PASSWORD_FILE DB_ROOT_PASSWORD_FILE DB_DATABASE"
for v in ${CLEAN_ENV}; do
    unset ${v}
done

echo "Starting PHP-FPM:"
# starting fpm and filter output to not show child process errors
#   cron.php starts processes in background which leads fpm to print messages like:
#     ALERT: oops, unknown child (3332) exited with code 0. Please open a bug report (https://bugs.php.net).
php-fpm | grep -v 'ALERT: oops, unknown child'
