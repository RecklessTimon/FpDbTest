#!/bin/sh

docker stop test_container
docker container remove test_container
docker image remove test_image

rm -f .env
