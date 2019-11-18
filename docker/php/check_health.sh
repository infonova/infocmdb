#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob

if CMD=$(cgi-fcgi -bind -connect 127.0.0.1:9000) ; then
  exit 0
fi

exit 1