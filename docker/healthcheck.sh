#!/bin/sh
set -eu

curl -fsS --max-time 5 http://127.0.0.1/up >/dev/null

if supervisorctl status | grep -Eq 'FATAL|BACKOFF|STOPPED|EXITED'; then
    exit 1
fi

exit 0
