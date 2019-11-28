#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob

CONFIG_DIR=/etc/nginx/

echo
echo "########################"
echo "# STARTING INIT SCRIPT #"
echo "########################"
echo

echo "Using Environment: ${APPLICATION_ENV}"
echo

/bootstrap/wait

echo "Copy default config"
cp -av /bootstrap/default-conf/*.conf ${CONFIG_DIR}
cp -avr /bootstrap/default-conf/conf.d/* ${CONFIG_DIR}/conf.d
echo

if [[ "${DOCKER_WEB_HOSTNAME}" == '' ]]; then
    echo "DOCKER_WEB_HOSTNAME not configured, please run ./run setup_all"
    exit 1
fi

export PHP_DISABLE_FUNCTIONS="fastcgi_param PHP_VALUE disable_functions='phpinfo';"
if [ "${APPLICATION_ENV}" = "testing" ] || [ "${APPLICATION_ENV}" = "development" ] ; then
    export PHP_DISABLE_FUNCTIONS=""
fi
DOCKER_INTERNAL_HOSTALIAS=
if [[ "${DOCKER_WEB_HOSTALIAS}" != "web" ]]; then
  export DOCKER_INTERNAL_HOSTALIAS="web"
fi


CONFD_PATH=/etc/nginx/conf.d/
DOCKER_WEB_HOSTNAME_CLEAN="${DOCKER_WEB_HOSTNAME//\./_}"
CERT_CONF_FULL_PATH=${CONFD_PATH}ssl_${DOCKER_WEB_HOSTNAME_CLEAN}.conf

if [[ ! -e ${CERT_CONF_FULL_PATH} ]]; then
  echo "Add default vhost for ${DOCKER_WEB_HOSTNAME}[Alias: ${DOCKER_WEB_HOSTALIAS} ${DOCKER_INTERNAL_HOSTALIAS}]: ${CONFIG_DIR}/conf.d/default.conf"
  cp ${CONFIG_DIR}/conf.d/default.conf.dist ${CONFIG_DIR}/conf.d/default.conf
  sed -i -e "s|#DOCKER_WEB_HOSTNAME#|${DOCKER_WEB_HOSTNAME}|"       "${CONFIG_DIR}/conf.d/default.conf"
  sed -i -e "s|#DOCKER_WEB_HOSTALIAS#|${DOCKER_WEB_HOSTALIAS}|"     "${CONFIG_DIR}/conf.d/default.conf"
  sed -i -e "s|#APPLICATION_ENV#|${APPLICATION_ENV}|"               "${CONFIG_DIR}/conf.d/default.conf"
  sed -i -e "s|#PHP_DISABLE_FUNCTIONS#|${PHP_DISABLE_FUNCTIONS}|"   "${CONFIG_DIR}/conf.d/default.conf"
  sed -i -e "s|#DOCKER_INTERNAL_HOSTALIAS#|${DOCKER_INTERNAL_HOSTALIAS}|" "${CONFIG_DIR}/conf.d/default.conf"
else
  echo "[NOTICE] Found SSL Configuration, skipping vhost-default configuration. [Config: ${CERT_CONF_FULL_PATH}]"

  # get rid of the file inside the docker instance if it exists
  rm -f "${CONFIG_DIR}/conf.d/default.conf"
fi

echo

if [[ -e /bootstrap/custom-conf ]]; then
  find /bootstrap/custom-conf -name "*.conf" -exec cp '{}' ${CONFIG_DIR} \;
fi

if [ "${APPLICATION_ENV}" = "testing" ] || [ "${APPLICATION_ENV}" = "development" ] ; then
    echo "Add vhost config for cmdb.test.local"
    DOCKER_WEB_HOSTALIAS=""
    DOCKER_INTERNAL_HOSTALIAS=""
    DOCKER_WEB_HOSTNAME="cmdb.test.local"
    APPLICATION_ENV="testing"

    cp ${CONFIG_DIR}/conf.d/default.conf.dist ${CONFIG_DIR}conf.d/testing.conf
    sed -i -e "s|#DOCKER_INTERNAL_HOSTALIAS#|${DOCKER_INTERNAL_HOSTALIAS}|" "${CONFIG_DIR}conf.d/testing.conf"
    sed -i -e "s|#DOCKER_WEB_HOSTNAME#|${DOCKER_WEB_HOSTNAME}|"     "${CONFIG_DIR}conf.d/testing.conf"
    sed -i -e "s|#DOCKER_WEB_HOSTALIAS#|${DOCKER_WEB_HOSTALIAS}|"   "${CONFIG_DIR}conf.d/testing.conf"
    sed -i -e "s|#APPLICATION_ENV#|${APPLICATION_ENV}|"             "${CONFIG_DIR}conf.d/testing.conf"
    sed -i -e "s|#PHP_DISABLE_FUNCTIONS#|${PHP_DISABLE_FUNCTIONS}|" "${CONFIG_DIR}conf.d/testing.conf"
    echo
fi

if [[ -n "$(ls /bootstrap/custom-conf/conf.d)" ]]; then
    echo "Copy custom config"
    cp -avr /bootstrap/custom-conf/conf.d/* ${CONFIG_DIR}conf.d/
    echo
fi

echo "Set permissions"
chmod -R 664       ${CONFIG_DIR}nginx.conf ${CONFIG_DIR}conf.d
chmod -R 700       ${CONFIG_DIR}conf.d/ssl/
chown -R root:root ${CONFIG_DIR}

echo "Execute next entrypoint script: $*"
exec "$@"
