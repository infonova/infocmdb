#!/usr/bin/env bash
set -Eeuo pipefail
shopt -s nullglob

cat <<"."
!!!!WARNING WARNING WARNING!!!!
This Script will simply more directories to be conform to the new structure

public/_uploads      ->   data/uploads
application/configs  ->   data/configs
library/perl         ->   data/library/perl
library/golang       ->   data/library/golang

Proceed only if you are sure about what you are doing!!!!!!
.

if [[ ! -f .env || ! -d _dist ]]; then
    echo "must be run from InfoCMDB Root Directory!"
    exit 0
fi

if [[ -d data/uploads ]]; then
    echo "data/uploads already exists, has the migration been run already?"
    exit 0
fi

if [[ -d application/configs ]]; then
    echo "application/configs already exists, has the migration been run already?"
    exit 0
fi

read -p "Do you accept the risk, type 'yes': " confirmAction
confirmed=${confirmAction:-no}
if [[ "${confirmed}" != "yes" ]]; then
    echo "exiting, nothing changed."
    exit 0
fi

read -p "is the service stopped?, type 'yes': " confirmAction
confirmed=${confirmAction:-no}
if [[ "${confirmed}" != "yes" ]]; then
    echo "exiting, nothing changed."
    exit 0
fi

echo "running migration of directories"

mkdir -p data/library

if [[ -d public/_uploads ]]; then
mv -v public/_uploads data/uploads
fi

if [[ -d application/configs ]]; then
mv -v application/configs data/configs
fi

if [[ -d library/perl ]]; then
    mv -v library/perl data/library/perl
fi

if [[ -d library/golang ]]; then
    mv -v library/golang data/library/golang
fi

cp -nr _dist/* ./
exit 1