MARiS XDS-Registry INSTALL

This application requires apache 1.3 or 2.0 and php 4.x (it dosn't work with php 5)

1. Create the database
MySQL case
a. Install MySQL 5.x
b. create the database for example 
mysql -u root
mysql> CREATE DATABASE XDS_REGISTRY;
mysql> grant all on XDS_REGISTRY.* to 'xds'@'localhost' identified by 'xds';
mysql> \q;
mysql -u xds -p -D XDS_REGISTRY < $install_path/MARIS_XDS_REGISTRY.sql

2. Configure the database access
Modify the config/registry_QUERY_mysql_db.php setting the right connection parameters

3. Open your browser to http://registry.ip/install_path/setup.php and configure the registry service using user marisxds / passoword marisxds
   In Registry Host DON'T use localhost or 127.0.0.1 but USE the local ip 10.x.x.x or 192.168.x.x

It easy to test this repository using xampp. We have tested with version 1.6.2, be carefull to change the php version to 4 and enable the dom function in php.ini file (extension=php_domxml.dll)