#!/usr/bin/env bash

# Check if symfony project exists
if ! [ -f /vagrant/app/AppKernel.php ] ; then
    echo "[Warning] You do not have symfony project created!"
    echo "[Info] Pull it from repository: https://github.com/lukasztecza/virtualTrainer.git"
    echo "[Info] Or create symfony project running: composer create-project symfony/framework-standard-edition virtualTrainer \"3.2.*\""
    echo "[Info] and move Vagrantfile, bootstrap.sh and .json files (composer, npm) files into it and run vagrant up in it again"
    echo "[Info] exiting ..."
    exit 1
fi

# Set versions and variables
APACHE_VERSION=2.4.7*
HOST=localhost
# PORT same as exposed in Vagrantfile
PORT=8080
MYSQL_VERSION=5.5
MYSQL_ROOT_PASSWORD=pass
MYSQL_USER=user
MYSQL_USER_PASSWORD=pass
MYSQL_HOST=localhost
MYSQL_DATABASE=virtual_trainer
PHP_VERSION=7.0

# Export variable to fix "dpkg-preconfigure: unable to re-open..." error
export DEBIAN_FRONTEND=noninteractive

# Add ondrej php repository
sudo add-apt-repository ppa:ondrej/php
apt-get update

# Install basic tools
apt-get install -y vim curl zip unzip

# Install apache and create symlink pointing default apache web dir to /vagrant
apt-get install -y apache2="$APACHE_VERSION"

# Create symlink from default apache web dir to /vagrant
if ! [ -L /var/www/html ]; then
    rm -rf /var/www
    mkdir /var/www
    ln -fs /vagrant /var/www/html
fi

# Enable mod_rewrite for apache
a2enmod rewrite

# Set ServerName to fix "AH00558: apache2: Could not reliably determine..." error
if ! fgrep ServerName /etc/apache2/apache2.conf; then
    echo "ServerName $HOST" | sudo tee -a /etc/apache2/apache2.conf
fi

# Set mysql answers and install mysql-server and mysql-client
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
apt-get install -y mysql-server-"$MYSQL_VERSION" mysql-client-"$MYSQL_VERSION"

# Set key_buffer_size to fix "Using unique option prefix key_buffer instead of key_buffer_size..." warning
if ! fgrep key_buffer_size /etc/mysql/my.cnf; then
    echo 'key_buffer_size = 16M' | sudo tee -a /etc/mysql/my.cnf
fi

# Install php and modules
apt-get install -y \
    php"$PHP_VERSION" \
    php"$PHP_VERSION"-curl \
    php"$PHP_VERSION"-mysql \
    php"$PHP_VERSION"-gd \
    php"$PHP_VERSION"-zip \
    php"$PHP_VERSION"-xml \
    php"$PHP_VERSION"-mbstring

# Display all errors for php
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/"$PHP_VERSION"/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/"$PHP_VERSION"/apache2/php.ini

# Allow large file uploads
#sed -i "s/memory_limit = .*/memory_limit = 32M/" /etc/php/"$PHP_VERSION"/apache2/php.ini
#sed -i "s/upload_max_filesize = .*/upload_max_filesize = 16M/" /etc/php/"$PHP_VERSION"/apache2/php.ini
#sed -i "s/post_max_size = .*/post_max_size = 24M/" /etc/php/"$PHP_VERSION"/apache2/php.ini

# Allow usage of .htaccess files inside /var/www/html
if ! fgrep "/var/www/html" /etc/apache2/apache2.conf; then
    cat >> /etc/apache2/apache2.conf <<EOL
# Listen and configure virtual host
Listen $PORT
<VirtualHost *:$PORT>
    # Not used as we expose port to the host machine
    # ServerName virtualtrainer.localhost
    # ServerAlias www.virtualtrainer.localhost

    # Set document root and block .htaccess files
    DocumentRoot /var/www/html/web
    <Directory /var/www/html/web>
        Require all granted
        AllowOverride None
        Order Allow,Deny
        Allow from All

        # Point to proper front controller
        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ app_dev.php [QSA,L]
        </IfModule>
    </Directory>

    # uncomment the following lines if you install assets as symlinks
    # or run into problems when compiling LESS/Sass/CoffeeScript assets
    # <Directory /var/www/html>
    #     Options FollowSymlinks
    # </Directory>

    # optionally disable the RewriteEngine for the asset directories
    # which will allow apache to simply reply with a 404 when files are
    # not found instead of passing the request into the full symfony stack
    <Directory /var/www/project/web/bundles>
        <IfModule mod_rewrite.c>
            RewriteEngine Off
        </IfModule>
    </Directory>

    ErrorLog /var/log/apache2/project_error.log
    CustomLog /var/log/apache2/project_access.log combined
</VirtualHost>
EOL
fi

# Set up database (note no space after -p)
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOL
CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE CHARACTER SET utf8 COLLATE utf8_general_ci;
GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO $MYSQL_USER@$MYSQL_HOST IDENTIFIED BY '$MYSQL_USER_PASSWORD';
FLUSH PRIVILEGES;
EOL

# If last command failed then exit
if [ $? != "0" ]; then
    echo "[Error] Database creation failed"
    echo "[Info] exiting ... "
    exit 1
fi

# Restart apache
service apache2 restart

# Install composer and virtual_trainer vendor packages (but prefer download and zip over cloning)
if ! command -v composer; then
    curl --silent https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Install phpunit
if ! command -v phpunit; then
    wget https://phar.phpunit.de/phpunit-6.1.phar
    chmod +x phpunit-6.1.phar
    mv phpunit-6.1.phar /usr/local/bin/phpunit
fi

# Go to synced folder (where project lives)
cd /vagrant

# Run composer install (read from composer.json)
if [ -f /vagrant/composer.json ]; then
    sudo -u vagrant -H sh -c "composer install --prefer-dist"
fi

# Install npm
curl -sL https://deb.nodesource.com/setup_7.x | sudo -E bash -
apt-get install -y nodejs

# Run npm install (read from package,json)
if [ -f /vagrant/package.json ]; then
    sudo -u vagrant -H sh -c "npm install"
fi

sudo -u vagrant -H sh -c "npm run gulp"

echo "[Info] Your project is available at:"
echo "[Info] $HOST:$PORT"
echo "[Info] By default development front controller is hit, to change it switch app_dev.php with app.php inside virtual machine in:"
echo "[Info] /etc/apache2/apache2.conf"
echo "[Info] And restart server:"
echo "[Info] sudo service apache2 restart"
echo "[Info] To execute tests go into virtual machine /vagrant directory and type:"
echo "[Info] phpunit"
echo "[Info] To rebuild assets go into virtual machine /vagrant directory and type:"
echo "[Info] npm run gulp"
echo "[Info] To track changes of css and js files in src directory go into virtual machine /vagrant directory and type:"
echo "[Info] npm run watch"
echo "[Info] To execute symfony or generator stuff you need only php-cli so you can run them from outside of virtual machine:"
echo "[Info] bin/console cache:clear"
echo "[Info] bin/console generate:doctrine:entity"
echo "[Info] bin/console generate:doctrine:crud"
echo "[Info] bin/console generate:doctrine:form"
echo "[Info] To execute doctrine stuff that need access to the database go into virtual machine /vagrant directory:"
echo "[Info] sudo php bin/console doctrine:migrations:status"
echo "[Info] sudo php bin/console doctrine:migrations:diff"
echo "[Info] sudo php bin/console doctrine:migrations:migrate"
