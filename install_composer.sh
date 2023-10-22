chmod -Rf 0777 .

curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' |   bash
apt install symfony-cli



curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer self-update
#chmod -Rf 0777 .
rm -Rf vendor/
rm composer.lock





composer require jms/serializer-bundle
composer require friendsofsymfony/rest-bundle
composer require symfony/maker-bundle
composer require symfony/orm-pack --update-with-all-dependencies





composer update
composer install --optimize-autoloader --apcu-autoloader --no-dev
composer dump-autoload -o
composer suggest --all
composer audit
