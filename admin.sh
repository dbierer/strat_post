#!/usr/bin/env bash
# Usage: admin.sh up|down|shell
echo "Usage: admin.sh up|down|build|ls|init|shell"
export CONTAINER="strat_post"
if [[ "$1" = "up" || "$1" = "start" ]]; then
    docker-compose up -d
elif [[ "$1" = "down" || "$1" = "stop" ]]; then
    docker-compose down
    echo "Resetting permissions back to owner..."
    sudo chown -R $USER:$USER *
elif [[ "$1" = "build" ]]; then
    docker-compose build --force-rm --no-cache
elif [[ "$1" = "shell" ]]; then
    docker exec -it $CONTAINER /bin/bash
elif [[ "$1" = "ls" ]]; then
    docker container ls
elif [[ "$1" = "init" ]]; then
    docker exec $CONTAINER /bin/bash -c "/tmp/init_apps.sh"
fi
