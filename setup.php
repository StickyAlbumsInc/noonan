k<?php

/*

  This is the setup script for the NGINX client proxy and associated
  supporting scripts. It requires (and checks to ensure) that you 
  already have NGINX and Certbot installed and properly configured.

*/

require './scripts/config.php';

echo "\n";

if(
  VHOST_PENDING_DIR == '' || 
  VHOST_ENABLED_DIR == '' ||
  VHOST_BAD_DOMAINS_DIR == '' ||
  NGINX_ENABLED_DIR == '' ||
  LE_CERTS_DIR == '' ||
  SERVER_IP == '' ||
  CERT_DATETIME_FORMAT == '' ||
  LOG_FILE == ''
) {
  die("Please edit values in 'scripts/config.php' before continuing.\n");
}

// The one time failing to write to the log *does* break everything. :)
if(!logMessage("Started Logging...")) {
  die("Cannot write to log file. Please check paths and permissions.\n");
} else {
  logMessage("Config OK...");
}

$nginx_check = shell_exec("nginx -v 2>&1");
if(!stristr($nginx_check, 'nginx version')) {
  logMessage("Can't find NGINX!");
  die("Can't find NGINX! Please install it before continuing.\n");
} else {
  logMessage("Found NGINX...");
}

// Note: If/when Certbot  hits 1.0+ this will need to be updated.
$certbot_check = shell_exec("certbot --version 2>&1");
if(!stristr($certbot_check, 'certbot 0')) {
  logMessage("Can't Find CERTBOT!");
  die("Can't find Certbot! Please install it before continuing.\n");
} else {
  logMessage("Found Certbot...");
}

function port_test($port) {
  $fp = @fsockopen(SERVER_IP, $port, $errno, $errstr, 0.1);
  if (!$fp) {
    return false;
  } else {
    fclose($fp);
    return true;
  }
}

if(!port_test(80)) {
  logMessage("Port 80 not open!");
  die("Port 80 not open! Please enable it in your firewall before continuing.\n");
} else {
  logMessage("Port 80 is open...");
}

if(!port_test(443)) {
  logMessage("Port 443 not open!");
  die("Port 443 not open! Please enable it in your firewall before continuing.\n");
} else {
  logMessage("Port 443 is open...");
}

if (!file_exists(VHOST_PENDING_DIR)) {
  if(!mkdir(VHOST_PENDING_DIR, 0777, true)) {
    logMessage("Unable to create vhost pending directory.");
    die("Unable to create vhost pending directory. - Please check file permissions on parent directory or manually create it.\n");
  } 
} else {
  logMessage("Vhost pending directory created or already exists...");
}

if (!file_exists(VHOST_ENABLED_DIR)) {
  if(!mkdir(VHOST_ENABLED_DIR, 0777, true)) {
    logMessage("Unable to create vhost enabled directory.");
    die("Unable to create vhost enabled directory. - Please check file permissions on parent directory or manually create it.\n");
  }
} else {
  logMessage("Vhost enabled directory created or already exists...");
}

if (!file_exists(VHOST_BAD_DOMAINS_DIR)) {
  if(!mkdir(VHOST_ENABLED_DIR, 0777, true)) {
    logMessage("Unable to create vhost bad domains directory.");
    die("Unable to create vhost bad domains directory. - Please check file permissions on parent directory or manually create it.\n");
  }
} else {
  logMessage("Vhost bad domains directory created or already exists...");
}

if(!is_readable(VHOST_PENDING_DIR)) {
  logMessage("Cannot read from vhost pending directory.");
  die("Cannot read from vhost pending directory. Please fix directory permissions.\n");
} else {
  logMessage("Vhost pending directory is readables...");
}

if(!is_readable(VHOST_ENABLED_DIR)) {
  logMessage("Cannot read from vhost enabled directory.");
  die("Cannot read from vhost enabled directory. Please fix directory permissions.\n");
} else {
  logMessage("Vhost enabled directory is readable...");
}

if(!is_readable(VHOST_BAD_DOMAINSD_DIR)) {
  logMessage("Cannot read from vhost bad domains directory.");
  die("Cannot read from vhost bad domains directory. Please fix directory permissions.\n");
} else {
  logMessage("Vhost bad domains directory is readable...");
}

if(!is_writable(VHOST_ENABLED_DIR)) {
  logMessage("Cannot write to vhost enabled directory.");
  die("Cannot write to vhost enabled directory. Please fix directory permissions.\n");
} else {
  logMessage("Vhost enabled directory writable...");
}

if(!is_writable(VHOST_BAD_DOMAINS_DIR)) {
  logMessage("Cannot write to vhost bad domains directory.");
  die("Cannot write to vhost bad domains directory. Please fix directory permissions.\n");
} else {
  logMessage("Vhost bad domains directory writable...");
}

logMessage("Setup Complete!");

echo "Setup complete! Please add the following lines to your crontab:\n\n";
echo "0 0 * * * sudo certbot renew";
echo "0 * * * * sudo php -q " . getcwd() . "/scripts/ssl_installer.php";
echo "0 2 * * * sudo php -q " . getcwd() . "/scripts/ssl_installer.php --second-chance=true";
echo "\nImportant: You must be able to run php scripts unattended via sudo.\n";
