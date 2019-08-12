<?php
 /*
  IMPORTANT: Must be ran via sudo.
  Run: Daily, after running expired_certs_remover.php
  This script renews SSL certificates that are about to expire. 
  
  It loops through the Certbot certificate directory to get a list of
  installed certificates and checks their expiration dates. If they
  expire within the next 24 hours, it does a manual renew/reinstall.
 */
require 'config.php';
require 'logger.php';
if(LOG_LEVEL == 'all') { logMessage("Removing long-expired certificates..."); }
$certs_dir          = LE_CERTS_DIR;           // The directory which holds the directories for all the certificates.
$date_format        = CERT_DATETIME_FORMAT;   // Certificate date format, usually 'M d H:i:s Y e'.
$bad_domains_dir    = VHOST_BAD_DOMAINS_DIR;  // Where bad domains go for time out.
$enabled_domain_dir = VHOST_ENABLED_DIR;      // Where enabled vhosts live.
$nginx_enabled_dir  = NGINX_ENABLED_DIR;      // Nginx symlink
$removed_certs_dir  = REMOVED_LE_CERTS_DIR;   // Where bad certs go to jail. 

 // Loop through the directory of Certbot certificates.
$dir = new DirectoryIterator($certs_dir);
foreach ($dir as $domain) {
  // Make sure we're skipping '.' and '..'
  if ($domain->isDir() && !$domain->isDot()) {
    if(LOG_LEVEL == 'all') { logMessage("Checking $domain..."); }

    $cert_info = shell_exec("openssl x509 -noout -dates -in " . LE_CERTS_DIR . "$domain/fullchain.pem  2>&1");
    if(!$cert_info) {
      if(LOG_LEVEL == 'all') { logMessage("Can't read info for for $domain."); }
    }
    // Split up the info and discard what we don't need to get the date as text.
    $lines = explode("\n", $cert_info);
    $text_date = str_replace('notAfter=', '', $lines[1]);
    // Convert it to a date object.
    $expiry_date = date_create_from_format("M d H:i:s Y e", $text_date);
    
    // Get future date (3 days from now)
    $current_date = new Datetime();
    $exp_date = $current_date->modify('+3 days');

    // If it's expiring within the next 3 days, renew the certificate.
    if($expiry_date < $exp_date) {
      if(LOG_LEVEL == 'all') { logMessage("Renewing SSL for $domain."); }
      try {
        shell_exec("certbot certonly -a webroot --webroot-path=/var/www/letsencrypt -m kelli@stickyfolios.com --agree-tos -d=$domain 2>&1");
        logMessage("Renewed SSL for $domain.");
      } catch (Exception $e) {
        logMessage($e->getMessage());
        logMessage("Could not enew SSL for $domain.");
      }
    } else {
      if(LOG_LEVEL == 'all') { logMessage("$domain has valid certificate."); }
    }
    if(LOG_LEVEL == 'all') { logMessage("Finished checking $domain."); }
  }
}
if(LOG_LEVEL == 'all') { logMessage("Finished renewing certificates..."); }