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