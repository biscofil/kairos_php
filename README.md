## Thelios

```shell
docker network create my-bridge-network --subnet=192.168.2.0/24
APP_IP=192.168.2.100 DB_IP=192.168.2.101 DOMAIN=peer0.biscofil.it docker-compose --project-name node1 up -d 
APP_IP=192.168.2.102 DB_IP=192.168.2.103 DOMAIN=peer1.biscofil.it docker-compose --project-name node2 up -d
```
