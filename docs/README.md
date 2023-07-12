# ACore CMS

ACore CMS based on wordpress.

- [Requirements](https://github.com/azerothcore/acore-cms#requirements)
- [Usage](https://github.com/azerothcore/acore-cms#usage)
- [Connect the CMS to AC and enable the shop](https://github.com/azerothcore/acore-cms/blob/master/docs/configure-cms.md)

Useful tutorials:
- [How to restrict the access with credentials of a specific web page](https://ubiq.co/tech-blog/how-to-password-protect-directory-in-nginx/)

## Requirements

- Docker & docker-compose
- Nodejs & npm

If you do not have **docker**, [install it](https://docs.docker.com/compose/install/).

On a Linux distro, you can install it via package manager, in a debian-based for example you can just run:
```
$ sudo apt install docker docker-compose
```

About **Nodejs & npm**, you can install it from [here](https://nodejs.org/en/).

## Usage
### Docker container


If you installed the requirements you are able to run the application using:
```
$ docker-compose up
```

It will download the related dependencies of the containers and start the acore-cms, next time you will need to just start the acore cms you can use:
```
$ npm run docker:start
```

Open your command prompt terminal (on Windows is for example PowerShell), navigate to the repository folder and issue the command:

```
docker-compose up
```

It will set up the docker container and download the necessary dependencies within.

Now you can see the website in [http://localhost:80/](http://localhost:80/).

Make sure that your port 80 is not already used by another service like Apache2, nginx etc.

If you want to change the port, you can change it from `.env` through the parameter `DOCKER_HTTP_PORTS` and `DOCKER_HTTPS_PORTS`.

Example:
```
DOCKER_HTTP_PORTS=8080:80
DOCKER_HTTPS_PORTS=443:443
```

The env variables above are used to configure the ports within the docker-compose file. To understand how port configurations work in docker-compose, please take a look at the [official documentation](https://github.com/compose-spec/compose-spec/blob/master/spec.md#ports)

**Note**: if you change this after the wordpress installation remember to change also the siteurl and related wordpress parameters in `wp_options` table.

**WARNING: if you run this in production, comment the phpmyadmin section in docker-compose to not expose the phpmyadmin service to any user or change the mysql credentials**

More info about docker-configuration are available below

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
$ npm run docker:logs
```

Show the logs of the running containers

```
$ npm run docker:db:export
```

Export the mysql database of the current wordpress installation inside the /data/sql folder (backup)

```
$ npm run docker:db:import
```

Import the sql files under /data/sql folder inside the mysql database of the current wordpress installation (restore backup)

## Configure docker

If you need to change some docker configuration (such as the exposed port or the configuration paths) you can create an `.env`
within the root of this project. This file is git-ignored and will be used by docker-compose to set some internal variables to
configure the containers.
The available configurations are available at the end of the `.env.docker` file where you can find some commented variables that you can copy/paste 
within the just created `.env` file and uncomment them to set them up.


### Using docker-compose.override.yml to extend the default one

If the .env variables that we provide are not enough for your configuration needs, you can always use the official ["docker-compose override" strategy](https://docs.docker.com/compose/extends/).

We've provided a sample `docker-compose.override.yml` file within the `/data/` directory that includes a phpmyadmin container. You can copy/paste that file
within the root directory of this project (it is git-ignored) and configure it as you prefer. Make sure to read the official docker-compose documentation first
to exactly understand how to use it.


## How to export/import database with the integrated tool

Acore-cms integrates a script under `/apps/db_exporter` folder that helps you to export the entire database in a SQL dump format.
This script uses the /conf/dist/conf.sh file to configure the db credentials. If you need to change those configurations, you can just
copy/paste that file inside the /conf/ folder to override default values (the files in that directory are git-ignored).

NOTE: by default sql files will be exported inside the /data/sql folder

### database export

`npm run docker:db:export`

### database import

`npm run docker:db:import`
