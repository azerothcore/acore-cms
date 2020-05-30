# ACore CMS

ACore CMS based on wordpress.

## Docker container

If you do not have docker, [install it](https://docs.docker.com/compose/install/).
On a Linux distro, you can install it via package manager, in a debian-based for example you can just run:
```
$ sudo apt install docker docker-composer
```

Well, now you should be able to run it using:
```
$ docker-compose up
```

or using npm:
```
$ npm run docker:start
````

### CLI commands available

```
$ npm run docker:start
````

Run the docekr webservice in foreground mode


```
$ npm run docker:start:d
````

Run the docker webservice in background (deamon)

```
$ npm run docker:shell
````

Run the docker webservice in background and open a bash shell inside the container

```
$ npm run docker:remove
````

Remove all created containers and their volumes

```
$ npm run docker:stop
````

Stop all running containers

```
$ npm run docker:db:export
````

Export the mysql database of the current wordpress installation inside the /data/sql folder (backup)

```
$ npm run docker:db:import
````

Import the sql files under /data/sql folder inside the mysql database of the current wordpress installation (restore backup)



