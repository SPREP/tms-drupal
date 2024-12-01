# Tonga Weather Mobile App API

Repository for the Tonga Weather Mobile App API using Drupal, Docker, Docker Compose, Docker4drupal

## Dev environment with [docker4drupal](https://github.com/wodby/docker4drupal/releases)

When starting for the first time copy the override-sample file and update as needed

```
cp docker-compose.override-sample.yml docker-compose.override.yml
```

Then start up docker-compose

```
docker-compose up -d

# OR

make up
```

Once installed you can access the dev site on port 8000. e.g. localhost:8000

## Contribute today!

First Signup for a [Gitpod account](https://gitpod.io/login/), then click the link below:

[![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/sprep/tms-drupal)


## Git revise

Install git revise using the installer to improve readability of your PR's.

## Bash Aliases

This repo includes some bash aliases to improve the Developer Experience (DX). Use `source .bash_aliases` if you wish to use them.

### Common commands

```
# start up dev environment
docker compose up -d #OR make

# stop environment
docker compose stop #OR make stop

# delete everything and start in a clean environment
docker compose down -v #OR make down

# check logs
docker compose logs -f #OR make logs

# check logs for specific container
docker compose logs -f php #OR make logs php

# log into php container (this will allow use of drush and composer)
docker compose exec php sh #OR make shell

```

**Tests**

`composer test-niwa-module`