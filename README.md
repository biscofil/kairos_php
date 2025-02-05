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

```

# Install

```shell
docker build -t biscofil/kairos_php:webserver .
docker tag biscofil/kairos_php:webserver biscofil/kairos_php:webserver-1.0.0
kind load docker-image biscofil/kairos_php:webserver-1.0.0

docker tag biscofil/kairos_php:webserver docker.io/kairos_php:webserver-1.0.0
# echo registryPass | docker login -u admin docker-registry.127.0.0.1.nip.io --password-stdin
kind load docker-image docker.io/kairos_php:webserver-1.0.0

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
# generate random values into helm_secret.ini
python3 generate_secret_ini_file.py
# TODO: manually insert missing values in helm_secret.ini
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


# Docker changes (deprecated)

```shell
# comment out the user part in DockerFile (gitlab)
docker build -t biscofil/kairos_php:no_user .
# uncomment the user part in DockerFile (local)
docker build -t biscofil/kairos_php:user .
docker push -a biscofil/kairos_php
```

