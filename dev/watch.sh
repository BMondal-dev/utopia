#!/bin/sh
# Hot-reload watcher for Swoole.
# Starts the PHP server, then kills and restarts it whenever a .php file
# changes inside /usr/src/code/src or /usr/src/code/app.

WATCH_DIRS="/usr/src/code/src /usr/src/code/app"
CMD="php app/http.php"

start_server() {
    echo "[watch] starting server..."
    $CMD &
    SERVER_PID=$!
}

stop_server() {
    if [ -n "$SERVER_PID" ] && kill -0 "$SERVER_PID" 2>/dev/null; then
        echo "[watch] stopping server (pid $SERVER_PID)..."
        kill "$SERVER_PID"
        wait "$SERVER_PID" 2>/dev/null
    fi
}

trap 'stop_server; exit 0' INT TERM

start_server

while true; do
    inotifywait -r -q -e modify,create,delete,move --include='\.php$' $WATCH_DIRS
    echo "[watch] change detected — reloading..."
    stop_server
    start_server
done
