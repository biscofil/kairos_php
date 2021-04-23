## Thelios

[![pipeline status](https://gitlab.com/biscofil/thelios/badges/master/pipeline.svg)](https://gitlab.com/biscofil/thelios/-/commits/master)

[![coverage report](https://gitlab.com/biscofil/thelios/badges/master/coverage.svg)](https://gitlab.com/biscofil/thelios/-/commits/master)


```shell
# comment out the user part in DockerFile
docker build -t registry.gitlab.com/biscofil/thelios:no_user .
# uncomment the user part in DockerFile
docker build -t registry.gitlab.com/biscofil/thelios:user .
docker push -a registry.gitlab.com/biscofil/thelios
```

# local
```shell
docker pull certbot/certbot
APP_NAME=Peer0 APP_URL=http://peer0.biscofil.it docker-compose build
U_ID=${UID} G_ID=${GID} docker-compose up -d
#export U_ID=$(id -u $USER)
#export G_ID=$(id -u $USER)
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
```

# on server peer20
```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d peer20.biscofil.it \
    --email filippo.bisconcin@gmail.com \
    --agree-tos
```

# on server peer21
```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d peer21.biscofil.it \
    --email filippo.bisconcin@gmail.com \
    --agree-tos
```


# on server peer22
```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d peer22.biscofil.it \
    --email filippo.bisconcin@gmail.com \
    --agree-tos
```


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

docker login registry.gitlab.com

U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
docker pull certbot/certbot
# RUN docker run -it --rm -v $(pwd)/letsencrypt/c.....
# enable SSL commenting 000-default.conf
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
php artisan key:generate
php artisan generate:jwt-keypair
```
