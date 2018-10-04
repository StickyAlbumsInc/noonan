<?php

/*

  This is the main configuration file. Here you will find various values
  that you must set, including paths to several directories, the log file
  location, and a few other miscellaneous values. 

*/

// All directories should include a trailing '/'.

// Your pending config files live here. The vhost enabler script will
// look here for sites that have not yet been installed and attempt to
// install them.
define('VHOST_PENDING_DIR', '/path/to/configs/pending/');

// Your enabled config files live here.  Once the vhost enabler has
// successfully installed the config file, it will live here. 
//
// While we technically could put this directly into NGINX's
// 'sites-enabled' it's not recommended. It's just easier to manage
// and back up everything when the congif files exist in a user's
// home directory.
define('VHOST_ENABLED_DIR', '/path/to/configs/enabled/');

// This is a special directory created for config files for domains
// that fail their DNS check. 
//
// When a configured domain fails its DNS check prior to SSL install
// it will be moved here, so that it's not clutting up the main
// queue of pending domains. 
//
// You can then re-run SSL install script on domains in this directory
// on a longer interval (e.g., once a day). 
define('VHOST_BAD_DOMAINS_DIR', '/path/to/config/bad_domains/');

// Because of this, however, we do need to create symlinks inside of
// NGINX's 'sites-enabled' directory. Set that path here.
define('NGINX_ENABLED_DIR', '/etc/nginx/sites-enabled/');

// Path to Let's Encrypt certificates - for certificate renewale
define('LE_CERTS_DIR', '/etc/letsencrypt/live/');

// Your proxy server's IP address - used for DNS validation.
define('SERVER_IP', '');

// This number represents the maximum number of certificates that will
// be installed at once. Any pending certificates exceeding the max
// amount will be installed the next time the script runs. 
//
// You may wish to set this number low for testing, but in production,
// it should be higher, based on how frequently you plan to execute
// the script.
define('MAX_INSTALLS_PER_EXECUTION', 100);

// This is the datetime format as returned by the OpenSSL certificate
// check that looks for expiring certificates. It's almost always
// "M d H:i:s Y e".
define('CERT_DATETIME_FORMAT', 'M d H:i:s Y e');

// The location of the log file for logging script a ctivity.
define('LOG_FILE', '/path/to/script-activity.log');

// The level of logging, currently 'all' or anything but 'all'.
define('LOG_LEVEL', 'lite');

