rsync -azP \
    --exclude 'sync.sh' \
    --exclude '.env' \
    --exclude 'db.env' \
    --exclude '.git' \
    --exclude '.idea' \
    --exclude 'node_modules/*' \
    --exclude 'bootstrap/cache/*' \
    --exclude 'storage/app/*' \
    --exclude 'storage/app/public/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/testing/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/logs/*' \
    --exclude 'mysql-data' \
    --exclude '_docker/*' \
    --exclude 'apache2.conf' \
    --exclude '000-default.conf' \
    --exclude 'resources/assets/*' \
    --exclude 'resources/js/*' \
    --exclude 'resources/sass/*' \
    ./ root@peer$1.biscofil.it:/root/helios
rsync -azP _docker/peer$1/ root@peer$1.biscofil.it:/root/helios

#ssh -t root@peer$1.biscofil.it 'cd /root/helios && docker-compose exec webserver php artisan config:cache'

#ssh -t root@peer$1.biscofil.it 'cd /root/helios && docker-compose exec webserver php artisan route:cache'

#ssh -t root@peer$1.biscofil.it 'cd /root/helios && docker-compose exec webserver php artisan migrate'

#ssh -t root@peer$1.biscofil.it 'cd /root/helios && docker-compose exec webserver composer dump-autoload'

ssh -t root@peer$1.biscofil.it 'cd /root/helios && chmod 777 -R . && clear && U_ID=$(id -u $USER) G_ID=$(id -u $USER) docker-compose restart'

notify-send Done $1

