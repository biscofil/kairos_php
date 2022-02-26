## Kairos Voting System

[![.github/workflows/docker-image.yml](https://github.com/biscofil/kairos_php/actions/workflows/docker-image.yml/badge.svg)](https://github.com/biscofil/kairos_php/actions/workflows/docker-image.yml)

Kairos is a Peer-2-Peer capable framework for end-to-end verifiable voting systems. The website is a SPA with Vue taking care of the front-end.

Master's Thesis in PDF: http://hdl.handle.net/10579/19696

Kairos implements a modular structure which allows to handle multiple question types, cryptosystems and anonymization methods.

[![.github/workflows/docker-image.yml](https://i0.wp.com/biscofil.it/wp-content/uploads/2021/08/modular_structure.png)](https://biscofil.it/kairos/)

Kairos started as a fork of Helios by Ben Adida (https://github.com/benadida/helios-server)

# Install

```shell

#install docker (https://docs.docker.com/engine/install/ubuntu/)
sudo apt-get update
sudo apt-get install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo \
  "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io

# install docker-compose (https://docs.docker.com/compose/install/)
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.1/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose


# disable SSL commenting 000-default.conf
mkdir helios

U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
docker pull certbot/certbot
# RUN docker run -it --rm -v $(pwd)/letsencrypt/c.....
# enable SSL commenting 000-default.conf
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
php artisan key:generate
php artisan generate:jwt-keypair
php artisan storage:link
```

# Adding SSL to the server domain.xyz
```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d domain.xyz \
    --email your@email.com \
    --agree-tos
```

# Docker changes

```shell
# comment out the user part in DockerFile (gitlab)
docker build -t biscofil/kairos_php:no_user .
# uncomment the user part in DockerFile (local)
docker build -t biscofil/kairos_php:user .
docker push -a biscofil/kairos_php
```

