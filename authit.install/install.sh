#!/bin/bash

### Is required pkgs is installed

do_next='1'
echo $do_next > do_next

./test-inst.sh apache2
./test-inst.sh libapache2-mod-php
./test-inst.sh php
./test-inst.sh postgresql
./test-inst.sh php-ssh2
./test-inst.sh sudo
./test-inst.sh php-pgsql

do_next=$(cat do_next)
echo ''
if [[ $do_next = '0' ]]; then
	echo Abort!
	exit	
else
	echo All packages is OK!
fi

### Apache 

echo -n 'Укажите имя хоста для сервера: '
read host_name
echo '<VirtualHost *:2222>
	ServerName '$host_name > /etc/apache2/sites-enabled/authit.conf
cat authit.conf >> /etc/apache2/sites-enabled/authit.conf
echo '# By Authit:
	Listen 2222' | cat - /etc/apache2/ports.conf > temp_port && mv temp_port /etc/apache2/ports.conf
mkdir /var/www/authit
cp -f -r files/* /var/www/authit/
echo 'PHP for apache2 enabling'
a2enmod php*
systemctl restart apache2

### Setting up psql DB
echo Seting up Postgesql
useradd authit
sudo -u postgres createuser 'authit'
sudo -u postgres createdb authit
echo -n Задайте пароль для БД:
read -s sqlpass
sudo -u postgres psql -c "ALTER USER authit PASSWORD '$sqlpass';"
sudo -u postgres psql -c 'GRANT ALL ON DATABASE authit TO authit;'

# Tables
sudo -u authit psql authit -c 'create table comps (id serial primary key, name varchar(32), os varchar(32), authtype varchar(32), cert varchar(64), crl varchar(64), drv varchar(64), forcelogon boolean, descr varchar(50), instdrv boolean, radserver varchar(32), fullpc bolean);'
sudo -u authit psql authit -c 'create table drvs (id serial primary key, os varchar(16), drvpath varchar(50), sctype varchar(36));'
sudo -u authit psql authit -c 'create table services (id serial primary key, comp int, srv varchar(32));'
sudo -u authit psql authit -c 'create table srvuse (comp int, srv int);'

# Заполним таблицу служб
for f in /etc/pam.d/*; do sudo -u authit psql authit -c "insert into services (srv) values ('"$(basename $f)"');"; done 2> /dev/null
# Убираем записи, действующие на весь комп
sudo -u authit psql authit -c "delete from services where srv like '%common-%';" 2> /dev/null

### Settings

echo 'host_name = '$host_name > /etc/authit.conf
echo 'sqlpass = '$sqlpass >> /etc/authit.conf

chown www-data:www-data /etc/authit.conf

echo 'Для начала работы наберите в браузере: http://localhost:2222'

rm do_next

