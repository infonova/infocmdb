#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob
SCHEDULERCOUNT=$(ps -eo cmd|grep '/app/infoCMDB'| wc -l);
if [[ ${SCHEDULERCOUNT} == 4 ]]; then
  exit 0
fi

exit 1