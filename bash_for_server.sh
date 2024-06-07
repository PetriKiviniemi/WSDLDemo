sudo cp /mnt/c/Users/asus/Desktop/repos/interoperability/ass5/* /var/www/html/
sudo chown www-data:www-data /var/www/html/output1.xml 
sudo chmod 664 /var/www/html/output1.xml
sudo service apache2 restart
