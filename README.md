## Kairos Voting System

[![.github/workflows/docker-image.yml](https://github.com/biscofil/kairos_php/actions/workflows/docker-image.yml/badge.svg)](https://github.com/biscofil/kairos_php/actions/workflows/docker-image.yml)

Kairos is a Peer-2-Peer capable framework for end-to-end verifiable voting systems. The website is a SPA with Vue taking care of the front-end.

Master's Thesis in PDF: http://hdl.handle.net/10579/19696

Kairos implements a modular structure which allows to handle multiple question types, cryptosystems and anonymization methods.

[![.github/workflows/docker-image.yml](https://i0.wp.com/biscofil.it/wp-content/uploads/2021/08/modular_structure.png)](https://biscofil.it/kairos/)

Kairos started as a fork of Helios by Ben Adida (https://github.com/benadida/helios-server)

# Configure Kubernetes

```shell

# install kind cluster
kind create cluster --config cluster-config.yaml
# kind delete clusters my-cluster

helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update
helm install ingress-nginx ingress-nginx/ingress-nginx
# wait some time!

# Install a private registry
helm repo add twuni https://helm.twun.io
helm install registry twuni/docker-registry --set ingress.enabled=true --set ingress.hosts={registry-docker-registry.127.0.0.1.nip.io}
helm uninstall registry
# helm upgrade registry twuni/docker-registry --set ingress.enabled=true --set ingress.hosts={registry-docker-registry.127.0.0.1.nip.io}
export POD_NAME=$(kubectl get pods --namespace default -l "app=docker-registry,release=registry" -o jsonpath="{.items[0].metadata.name}")
kubectl -n default port-forward $POD_NAME 5050:5000 &

```

# Install

```shell

docker build -t biscofil/kairos_php:webserver .
docker tag biscofil/kairos_php:webserver biscofil/kairos_php:webserver-1.0.0
docker push biscofil/kairos_php:webserver-1.0.0

#docker tag biscofil/kairos_php:webserver localhost:5050/kairos_php:webserver
#docker push localhost:5050/kairos_php:webserver

#docker tag biscofil/kairos_php:webserver registry-docker-registry.127.0.0.1.nip.io/kairos_php:webserver
#docker push registry-docker-registry.127.0.0.1.nip.io/kairos_php:webserver

# docker tag biscofil/kairos_php:webserver localhost:5050/kairos_php:webserver
# docker push localhost:5050/kairos_php:webserver

# SSL

./cert.sh

openssl req -x509 -nodes -days 2 -newkey rsa:2048 -keyout ingress-tls.key -out ingress-tls.crt -subj "/CN=kairos-webserver.127.0.0.1.nip.io"

kubectl delete secret my-tls-secret 
kubectl create secret tls my-tls-secret --key ingress-tls.key --cert ingress-tls.crt
rm ingress-tls.key ingress-tls.crt

# Config

helm package helm

# Deploy one node
kubectl create ns node1
# TODO generate random values in helm_secret.ini
# php artisan key:generate
# php artisan generate:jwt-keypair

kubectl create secret generic kairos-secrets --from-env-file=helm_secret.ini --namespace node1
helm install kairos Kairos-0.1.0.tgz --namespace node1 -f values.yaml
helm upgrade kairos Kairos-0.1.0.tgz --namespace node1 -f values.yaml
# cleanup:
# helm uninstall kairos --namespace node1
```

# TODOs

- Helm
  - use the same docker image for webserver and scheduler (done)
  - share storage between webserver, scheduler and migration_job
    - remove folder creation in docker image
- adapt `php artisan generate:jwt-keypair` to kubernertes

# Legacy (deprecated)

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
# php artisan key:generate
# php artisan generate:jwt-keypair
# php artisan storage:link
```

# Adding SSL to the server domain.xyz (deprecated)

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

# Docker changes (deprecated)

```shell
# comment out the user part in DockerFile (gitlab)
docker build -t biscofil/kairos_php:no_user .
# uncomment the user part in DockerFile (local)
docker build -t biscofil/kairos_php:user .
docker push -a biscofil/kairos_php
```

