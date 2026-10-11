#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PGDATA="$ROOT/.pgdata"
PGLOG="$PGDATA/pg.log"

find_pg_bin() {
  local name="$1"
  if command -v "$name" >/dev/null 2>&1; then
    command -v "$name"
    return
  fi
  local candidate
  for candidate in /usr/lib/postgresql/*/bin/"$name"; do
    if [ -x "$candidate" ]; then
      echo "$candidate"
      return
    fi
  done
  echo ""
}

PGCTL="$(find_pg_bin pg_ctl)"
PSQL="$(find_pg_bin psql)"

if [ -z "$PGCTL" ]; then
  echo "pg_ctl not found. Install PostgreSQL 16 client/server tools." >&2
  exit 1
fi

if [ ! -d "$PGDATA" ]; then
  echo "Missing $PGDATA" >&2
  exit 1
fi

if [ -f "$PGDATA/postmaster.pid" ]; then
  pid="$(head -1 "$PGDATA/postmaster.pid" || true)"
  if [ -n "$pid" ] && ! kill -0 "$pid" 2>/dev/null; then
    echo "Removing stale Postgres pid $pid"
    rm -f "$PGDATA/postmaster.pid"
  fi
fi

if "$PGCTL" -D "$PGDATA" status >/dev/null 2>&1; then
  echo "Postgres already running on 127.0.0.1:5433"
else
  # Absolute unix-socket dir: relative .pgdata fails after postmaster cds into the data directory.
  "$PGCTL" -D "$PGDATA" -l "$PGLOG" -o "-p 5433 -h 127.0.0.1 -k ${PGDATA}" start
fi

if [ -z "$PSQL" ]; then
  echo "psql not found; skipping role/db ensure"
  exit 0
fi

ready=0
for _ in 1 2 3 4 5 6 7 8 9 10; do
  if "$PSQL" -h 127.0.0.1 -p 5433 -d postgres -c 'SELECT 1' >/dev/null 2>&1 \
    || PGPASSWORD=postgres "$PSQL" -h 127.0.0.1 -p 5433 -U postgres -d postgres -c 'SELECT 1' >/dev/null 2>&1 \
    || "$PSQL" -h "$PGDATA" -p 5433 -d postgres -c 'SELECT 1' >/dev/null 2>&1; then
    ready=1
    break
  fi
  sleep 0.4
done

if [ "$ready" -ne 1 ]; then
  echo "Postgres started but is not accepting connections. See $PGLOG" >&2
  exit 1
fi

run_sql() {
  "$PSQL" -h 127.0.0.1 -p 5433 -d postgres -v ON_ERROR_STOP=1 -c "$1" >/dev/null 2>&1 \
    || PGPASSWORD=postgres "$PSQL" -h 127.0.0.1 -p 5433 -U postgres -d postgres -v ON_ERROR_STOP=1 -c "$1" >/dev/null 2>&1 \
    || "$PSQL" -h "$PGDATA" -p 5433 -d postgres -v ON_ERROR_STOP=1 -c "$1" >/dev/null 2>&1
}

run_sql "DO \$\$ BEGIN CREATE ROLE postgres WITH LOGIN SUPERUSER PASSWORD 'postgres'; EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;" || true
run_sql "ALTER ROLE postgres WITH LOGIN SUPERUSER PASSWORD 'postgres';" || true
run_sql "SELECT 1 FROM pg_database WHERE datname = 'live_admin'" >/dev/null 2>&1 || true
run_sql "CREATE DATABASE live_admin OWNER postgres;" || true

echo "Postgres ready at 127.0.0.1:5433"
