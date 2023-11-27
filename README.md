# Tonga Met App Backeend

Tonga Met App Backeend

## Dev environment with [docker4drupal](https://github.com/wodby/docker4drupal/releases)

When starting for the first time copy the override-sample file and update as
needed

```
cp docker-compose.override-sample.yml docker-compose.override.yml
```

Also update the .env file with your project name. Then start up docker-compose

```
docker-compose up -d
```

The settings.docker.php file will map into the docker environment as a
settings.local.php file for environment specific configs. This is a good place
to put sensitive configs (not for commiting to git) such as passwords. On
production you can create a settings.local.php and override the configs.

Once installed you can access the dev site on port 8000. e.g. localhost:8000

**Common commands**

```
# start up dev environment
docker-compose up -d

# stop environment
docker-compose stop

# delete everything and start in a clean environment
docker-compose down -v

# check logs
docker-compose logs -f

# check logs for specific container
docker-compose logs -f php

# log into php container (this will allow use of drush and composer)
docker-compose exec php sh

```

**Tests**

* See [ainsofs/drupal-project](https://github.com/ainsofs/drupal-project) for
gitlab ci  and composer tests
