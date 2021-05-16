rsync -azP \
    --exclude '.env' \
    --exclude 'db.env' \
    --exclude '.git' \
    --exclude '.idea' \
    --exclude 'node_modules/*' \
    --exclude 'bootstrap/cache/*' \
    --exclude 'storage/app/public/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/testing/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/logs/*' \
    --exclude 'mysql-data' \
    --exclude '_docker/*' \
    --exclude 'apache2.conf' \
    --exclude 'resources/assets/*' \
    --exclude 'resources/js/*' \
    --exclude 'resources/sass/*' \
    ./ root@peer20.biscofil.it:/root/helios
rsync -azP _docker/peer20/ root@peer20.biscofil.it:/root/helios



rsync -azP \
    --exclude '.env' \
    --exclude 'db.env' \
    --exclude '.git' \
    --exclude '.idea' \
    --exclude 'node_modules/*' \
    --exclude 'bootstrap/cache/*' \
    --exclude 'storage/app/public/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/testing/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/logs/*' \
    --exclude 'mysql-data' \
    --exclude '_docker/*' \
    --exclude 'apache2.conf' \
    --exclude 'resources/assets/*' \
    --exclude 'resources/js/*' \
    --exclude 'resources/sass/*' \
    ./ root@peer21.biscofil.it:/root/helios
rsync -azP _docker/peer21/ root@peer21.biscofil.it:/root/helios


rsync -azP \
    --exclude '.env' \
    --exclude 'db.env' \
    --exclude '.git' \
    --exclude '.idea' \
    --exclude 'node_modules/*' \
    --exclude 'bootstrap/cache/*' \
    --exclude 'storage/app/public/*' \
    --exclude 'storage/framework/cache/data/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/testing/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/logs/*' \
    --exclude 'mysql-data' \
    --exclude '_docker/*' \
    --exclude 'apache2.conf' \
    --exclude 'resources/assets/*' \
    --exclude 'resources/js/*' \
    --exclude 'resources/sass/*' \
    ./ root@peer22.biscofil.it:/root/helios
rsync -azP _docker/peer22/ root@peer22.biscofil.it:/root/helios




