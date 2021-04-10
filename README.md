## Thelios

```shell
docker pull certbot/certbot
APP_NAME=Peer0 APP_URL=http://peer0.biscofil.it docker-compose build
U_ID=${UID} G_ID=${GID} docker-compose up -d
#export U_ID=$(id -u $USER)
#export G_ID=$(id -u $USER)
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
```

# on server peer10

```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d peer10.biscofil.it \
    --email filippo.bisconcin@gmail.com \
    --agree-tos
```

# on server peer11

```shell
docker pull certbot/certbot
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose build
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose up -d
U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose down
docker run -it --rm -v $(pwd)/letsencrypt/certs:/etc/letsencrypt -v $(pwd)/letsencrypt/data:/data/letsencrypt \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/data/letsencrypt \
    -d peer11.biscofil.it \
    --email filippo.bisconcin@gmail.com \
    --agree-tos
```
