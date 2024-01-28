#!/bin/sh

envArg=''

if [ -f .env ]; then
    . ./.env
    envArg='--env-file .env'
fi

docker container remove test_container
docker image remove test_image

docker build -t test_image .
docker container create \
    --name test_container \
    --network host \
    ${envArg} \
    --volume .:/srv/app \
     test_image
