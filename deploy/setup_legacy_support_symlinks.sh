set -Eeuo pipefail
shopt -s nullglob

cat <<"."
!!!!WARNING WARNING WARNING!!!!

This script created symlinks from the data directory to the old structure so that apache installation work.

YOU MUST SETUP THE DIST DATA FIRST!

run: cp -nvr ./_dist/* .
.

if [[ !-d data/uploads ]]; then
    echo "data/uploads doesn't exist, has the structure been setup?"
    echo "run: cp -nvr ./_dist/* ."
    echo ""
    exit 0
fi

if [[ !-d public/_uploads ]]; then
    ln -s data/uploads public/_uploads
fi

if [[ !-d application/config ]]; then
    ln -s data/configs application/config
fi

if [[ !-d library/perl ]]; then
    ln -s data/library/perl library/perl
fi

if [[ !-d library/golang ]]; then
    ln -s data/library/golang library/golang
fi
