#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob

CONFIG_DIR=/etc/mysql/conf.d/

cp -anvr /bootstrap/custom-conf/* ${CONFIG_DIR}

echo "Execute next entrypoint script: $@"
exec "$@"