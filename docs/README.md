# ACore CMS

ACore CMS based on wordpress.

- [Requirements](https://github.com/azerothcore/acore-cms#requirements)
- [Usage](https://github.com/azerothcore/acore-cms#usage)
- [Configure and connect the CMS to AC](https://github.com/azerothcore/acore-cms/docs/configure-cms.md)

## Requirements

- Docker & docker-compose
- Nodejs & npm

If you do not have **docker**, [install it](https://docs.docker.com/compose/install/).

On a Linux distro, you can install it via package manager, in a debian-based for example you can just run:
```
$ sudo apt install docker docker-composer
```

About **Nodejs & npm**, you can install it from [here](https://nodejs.org/en/).

## Usage
### Docker container


If you installed the requirements you can be able to run the application using:
```
$ docker-compose up
```

It will download the related dependencies of the containers and start the acore-cms, next time you will need to just start the acore cms you can use:
```
$ npm run docker:start
```

Now you can see the website in [http://localhost:81/](http://localhost:81/).

If you want to change the port, you can change it from `docker-comopse.yaml` changing the following properties under `web.local` from:
```
    ports:
      - "81:80"
```

to:
```
    ports:
      - "80:80"
```

Be sure that your port 80 is not already used by another service like Apache2, nginx etc.

### CLI commands available

```
$ npm run docker:start
```

Run the docekr webservice in foreground mode


```
$ npm run docker:start:d
```

Run the docker webservice in background (deamon)

```
$ npm run docker:shell
```

Run the docker webservice in background and open a bash shell inside the container

```
$ npm run docker:remove
```

Remove all created containers and their volumes

```
$ npm run docker:stop
```

Stop all running containers

```
$ npm run docker:db:export
```

Export the mysql database of the current wordpress installation inside the /data/sql folder (backup)

```
$ npm run docker:db:import
```

Import the sql files under /data/sql folder inside the mysql database of the current wordpress installation (restore backup)

## Configure docker

acore-cms uses our `jsdocker-compose` tool to run docker-compose services, it's an advanced tool that comes with a fully configurable CLI.

You can override some docker-compose options using the " -f" options: [official documentation](https://docs.docker.com/compose/reference/overview/#use--f-to-specify-name-and-path-of-one-or-more-compose-files)

To do it with our scripts you can create an .env.local file inside the root directory of this project. All variables specified inside the .env.local will override the variables existing inside the default .env file, so for instance you can copy and change the following variable:

```
# override extra arguments for docker
DOCKER_EXTRA_ARGS=" -f docker-compose.default.yml"
```

To something like:

```
DOCKER_EXTRA_ARGS=" -f docker-compose.override.yml"
```

Finally you can create your `docker-compose.override.yml` file with your own settings


