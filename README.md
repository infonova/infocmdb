# InfoCMDB
 [![Build Status](https://travis-ci.com/infonova/infocmdb.svg?branch=master)](https://travis-ci.com/infonova/infocmdb)
 
 **Highly Customizable Configuration Management Database**
 
![InfoCMDB Slideshow](/public/assets/images/gh_images.gif)

## Screenshots

* [Login](/public/assets/images/001_Login.png)
* [Ci Index](/public/assets/images/002_CI_Index.png)
* [Ci Detail](/public/assets/images/003_CI_Detail.png)
* [Ci Relations](/public/assets/images/004_CI_Relations.png) 

## Overview

* Granular AccessControl (RBAC)
* Custom Configuration Items (CI)
* Different Attribute Datatypes
* Automation with Workflows using GoLang, Perl SDKs

## Getting Started

To setup a new instance follow these steps for either production or development.

### Prerequisites

* linux x86
* minimum hardware required of 2 cores and 4 gb ram
* docker
* docker-compose

### Installing

Clone the infocmdb repository

```sh
git clone https://github.com/infonova/infocmdb
```

Start the infocmdb using the provided run command 

```sh
./run up
```

This will perform all setup steps required to configure the Docker-Environment.

```log
Running setup_env...
Create .env file...
Choose an IMAGE_TAG you want to use [Env: IMAGE_TAG][latest]: 
.env.example -> .env
Setting image tag: latest
Generating random Root Password
Generating random 'infocmdb' User Password
Running setup_nginx...
Choose hostname for nginx vhost [localhost]: infocmdb.prod.local
Set 'infocmdb.prod.local' as DOCKER_WEB_HOSTNAME in .env file
Running setup_docker...
Setting up docker override per environment.
Choose Environment [Env: COMPOSE_ENV] (prod/dev/test) [prod]: prod
  Creating symlink: docker-compose-prod.yml --> docker-compose.override.yml

Use this command to start application:
 ./run up 

Login at: http://infocmdb.prod.local || https://infocmdb.prod.local
```

## SSL Certificate

Inside the `web` container you can add additional configuration in the `/bootstrap/custom-conf` directory.

Using the docker-compose for example:

````yaml
    volumes:
      - "./docker/nginx/custom-conf:/bootstrap/custom-conf:ro"
````

### Generate dhparams

To use ssl we generate a dhparam file to enable forward secrecy. 

```sh
./run generate_dhparam                             
Running generate_dhparam...
Create Diffie Hellman param for nginx
Sending build context to Docker daemon  2.048kB
Step 1/2 : FROM alpine
 ---> b7b28af77ffe
Step 2/2 : RUN apk update &&   apk add --no-cache openssl &&   rm -rf /var/cache/apk/*
 ---> Using cache
 ---> ac67e96cd7fb
Successfully built ac67e96cd7fb
Successfully tagged openssl:latest
Generating DH parameters, 4096 bit long safe prime, generator 2
This is going to take a long time
.......... <this takes a long time> ...........
```

### Generate self-signed-certificate and vhost configuration

```sh
./run gencert infocmdb.local                                    
Running generate_dhparam...
  Already exists 'docker/nginx/custom-conf/conf.d/ssl/dhparam.pem'.
Generating certificate for infocmdb.local
Generating a RSA private key
....................................++++
..........................................................................................................................++++
writing new private key to '/app/docker/nginx/custom-conf/conf.d/ssl/infocmdb_local.key'
-----
Saved certificate to docker/nginx/custom-conf/conf.d/ssl/infocmdb_local... key/crt
Created docker/nginx/custom-conf/conf.d/ssl_infocmdb_local.conf
```

## RUN Commands

To automate tasks the `./run` command can be used

```text
####################
# infoCMDB Console #
####################

Utility for handling an infoCMDB installation

Usage:
  run <command> [--non-interactive] [--command-options] [<arguments>]
  run -h | --help

Options:
  -h --help           Display this help information.
  --non-interactive   Run without asking questions and apply configuration defaults

Help:
  run help [<command>]

Available commands:
  bash
  build
  clean_cache
  commands
  container_name
  cron
  data_backup
  data_import
  destroy
  down
  edit
  gencert
  help
  mysql
  mysql_backup
  mysql_dump
  mysql_import
  restart
  setup
  setup_all
  setup_docker
  setup_env
  setup_lib
  setup_nginx
  setup_nginx_ssl
  up
  update_run
  version
```

## Backup

For creating backups of a running infoCMDB instance mysql and data can be dumped

### Database (mysql)

THIS WILL DESTROY ALL EXISTING DATA!

```sh
./run mysql_backup
Completed mysqldump: dump-2019-10-30_152732.sql.gz
./run mysql_import dump-2019-10-30_152732.sql.gz
```

### Volume Data (`/app/data`)

THIS WILL DESTROY ALL EXISTING DATA!
```sh
./run data_backup
Completed databackup: data-2019-10-30_152819.tar.gz
./run data_import data-2019-10-30_152819.tar.gz
```

## Optional

### Mountpoints / Directories

* `/app/data` all working files are stored in this directory.
* `/app/data/configs` is used to store all configuration files. 

In case you want to directly interact with the cmdb data, for development
or testing you can modify the mount point.

`docker-compose.yml`:

```yaml
services:
...
  php:
    ...
    volumes:
      ...
      - "./<bind_directory_data>:/app/data:cached"
...
```

This is not recommended for productive environments and often leads to 
permission or performance issues on windows.

### MySQL Database Port

If required it is possible to add an export for the mysql ports.

Either enable the option in the `docker-compose.yml` or add it to the `docker-compose.override.yml`:

```yaml
version: "3.4"

services:
  mariadb:
    ports:
      - '${DOCKER_DB_PORT}:3306'
```

Set the `DB_PORT` in the `.env` file: 

```ini
DB_PORT=3306
```

## License

This project is licensed under the Apache License 2.0 - see LICENSE file for details.
