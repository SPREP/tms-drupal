# Tonga Met App Backend

Tonga Met Mobile App Backend using Drupal. The dev environment has been
setup to work with docker4drupal and lando.

## Docker4drupal instructions

### Prerequisites
1. Install docker with docker compose

### Setup
1. Clone the repository
2. Copy docker-compose.override-sample.yml

`cp docker-compose.override-sample.yml docker-compose.override.yml`

3. Modify docker-compose.override.yml as needed e.g. Add the API tokens
4. Start docker compose

`docker compose up -d`

5. Log into the PHP container to get access to composer and drush

`docker compose exec php sh`

6. Run drush uli to access the site

`drush uli`

### Optional

**Load a backup of the db.**

1. Copy the backup into mariadb-init/
2. Restart mariadb container

```
docker composer stop mariadb
docker composer rm -f mariadb
docker compose up -d

# check mariadb logs
docker compose logs mariadb
```

**Configure env specific variables.**

The settings.docker.php file will map into the docker environment for
environment specific configs. On production you can create a settings.local.php
and override the configs.

Once installed you can access the dev site on port 8000. e.g. tms.docker.localhost:8000

## Tests

```
# To run tests locally log into the php container

docker compose exec php sh

# then run

composer test
```

## Security

```
# start up dev environment
docker compose up -d

# stop environment
docker compose stop

# delete everything and start in a clean environment
docker compose down -v

# check logs
docker compose logs -f

# check logs for specific container
docker compose logs -f php

# log into php container (this will allow use of drush and composer)
docker compose exec php sh
```

Learn about the [Drupal trademark and logo policy here][trademark].

## Bash aliases

You can use these bash alias to speed up use of commands in your local dev.

```
# docker aliases
alias dup='docker compose up -d'
alias dstop='docker compose stop'
alias drm='docker compose rm'

alias dphp='docker compose exec php bash'

alias dl="docker compose logs -f"
alias dlphp='docker compose logs -f php'

alias dc='docker compose'
```
