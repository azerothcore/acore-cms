## Configure and connect the CMS to acore-docker

### Run acore-docker

First of all, download acore docker and run it

```bash
git clone https://github.com/azerothcore/acore-docker
cd acore-docker
```

Run your game server:

```bash
docker compose up
# or "docker compose up -d" to run it in background
```

After running your game server you could copy the worldserver.conf file from your worldserver docker container into a local directory using:

```bash
docker compose cp ac-worldserver:/azerothcore/env/dist/etc/worldserver.conf conf
```

Achieving this you can override your worldserver.conf using a docker volume adding the following line under `volumes:` (line 55) in your acore-docker docker-compose.yml file:

```bash
- ./conf/worldserver.conf:/azerothcore/env/dist/etc/worldserver.conf
```

To let acore-cms connecting via SOAP into your worldserver is important to enable SOAP via worldserver.conf, changing the following configurations (line ~430):

```conf
SOAP.Enabled = 1
SOAP.IP = "0.0.0.0"
```

Moreover, you have to use a shared network for the acore-cms and acore-docker containers, acore-cms already have a `local-shared-net`, you could re-use it on acore-docker by adding in your acore-docker docker-compose.yml file the following lines.

- under `x-networks` (line 4) you have to add the `local-shared-net`

```yml
x-networks: &networks
  networks:
    - ac-network
    - local-shared-net
```

then, define the network details under `networks` (line 147-148)

```yml
networks:
  ac-network:
  local-shared-net:
    name: local-shared-net
    driver: bridge
```

Once you updated them, recreate your docker containers using:

```bash
docker compose down
docker compose up -d
```

To access your game server console you can run

```bash
docker attach acore-docker-ac-worldserver-1
```

then, create your game account and set its gmlevel to 3 running:

```
account create <user> <password> <confirm password>
account set gmlevel <user> 3 -1
```

Note: you can detach from the docker session using Ctrl+p and Ctrl+q sequence.

Now you should be able to log-in into your game server using `127.0.0.1` (or `acore-docker-ac-worldserver-1`) as realmlist and with the new account, moreover, setting gmlevel 3 will allow the connection via SOAP from acore-cms using this new account.

### Run acore-cms

First, clone this repository:

```bash
git clone https://github.com/azerothcore/acore-cms
```

then, re-use the `local-shared-net` also in the php acore-cms docker container so your website will be able to connect to the acore-docker containers, adding `local-shared-net` under the service php (line 60):

```yml
networks:
  - local-private-net
  - local-shared-net
```

Afterward, you can visit the local website in [http://localhost](http://localhost), do the setup of the website and configure the acore-wp-plugin to connect properly the website with the game server, so:

- go to the admin panel of the CMS (`localhost/wp-admin/wp-admin.php`)
- on `Plugins -> Installed Plugins`
- Activate `AzerothCore Wordpress Integration`
- Activated, go to `WP admin panel -> AzerothCore -> Realm Settings`, fill all the fields with the credentials and save. Keep in mind that as `host` you have to put the docker container name, you can read them using `docker ps`, by default your credentials should be as follows:
  - SOAP
    - Host: acore-docker-ac-worldserver-1
    - Port: 7878
    - User & Pass: an account with gmlevel 3
    - Note: after saving the settings changes you can verify SOAP using the button "Check SOAP"
  - Auth, Character and World have the same database credentials but different db name
    - Host: acore-docker-ac-database-1
    - Port: 3306
    - User: root
    - Pass: password
  - Database Auth Name: acore_auth
  - Database Character Name: acore_characters
  - Database World Name: acore_world

Now if you click on "Check SOAP" it should work printing the .server info output command text.
For the remainder acore-cms configurations you should be able to follow the main guide [here](https://github.com/azerothcore/acore-cms/blob/master/docs/configure-cms.md) from the section "Register account on the game server".

---

### Troubleshooting

Your acore-docker docker-compose.yml file should be like [this one](https://gist.github.com/Helias/c541edbef85d8992311e4ec049961920#file-acore-docker_docker-compose-yml).

Your acore-cms docker-compose.yml file should be like [this one](https://gist.github.com/Helias/c541edbef85d8992311e4ec049961920#file-acore-cms_docker-compose-yml).

You can compare your local files to see if the conf matches, or you can download directly these two docker-compose.yml files and replace with what you already have after cloning acore-docker and acore-cms repositories.

For any issues check [this troubleshooting section](https://github.com/azerothcore/acore-cms/blob/master/docs/configure-cms.md#troubleshooting) or ask help on [Discord](https://discord.gg/gkt4y2x) in the channel `#acore-cms` (section `TOOLS`), you can also tag @Helias for any issue about this CMS.
