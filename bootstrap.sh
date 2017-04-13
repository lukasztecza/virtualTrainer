#!/usr/bin/env bash

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
PHP_VERSION=7.1

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
apt-get install -y php"$PHP_VERSION" php"$PHP_VERSION"-curl php"$PHP_VERSION"-mysql php"$PHP_VERSION"-gd php"$PHP_VERSION"-zip php"$PHP_VERSION"-xml

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
    echo "[Error]: Database creation failed. Aborting."
    exit 1
fi

# Restart apache
service apache2 restart

# Install composer and virtual_trainer vendor packages (but prefer download and zip over cloning)
if ! command -v composer; then
    curl --silent https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Run composer install or notify to create project (prefer downloading over cloning)
cd /vagrant
if  [ -d src ]; then
    sudo -u vagrant -H sh -c "composer update --prefer-dist"
else
    echo "[Info] Create symfony project running: composer create-project symfony/framework-standard-edition virtualTrainer \"3.2.*\""
fi
echo "[Info] Your project is available at $HOST:$PORT"
