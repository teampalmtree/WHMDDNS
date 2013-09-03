WHMDDNS (WHM Dynamic DNS Updater Script)
========================================

INSTALLATION
============

1. Rename WHMDDNS.confip.php.example to WHMDDNS.confip.php and fill out your WHM URL.
2. Upload WHMDDNS.confip.php and WHMDDNS.php to a directory on a web server running PHP5.3+.
3. Set up your router's dynamic IP address settings to use a customer update URL:
http://[USERNAME]:[PASSWORD]@webserver.com/WHMDDNS.php?hostname=[DOMAIN]&myip=[IP]
4. The username/password used for DDNS updates is your WHM username/password.
