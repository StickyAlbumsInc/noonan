<?php

/*

  IMPORTANT: Must be ran via sudo.

  Run: Daily, before new certificates are installed.

  This script renews SSL certificates that are about to expire. It
  loops through the Certbot certificate directory to get a list of
  installed certificates and checks their expiration dates
  individually, rather than running `certbot-auto renew`. This is
  done to decreate the number of calls to the ACME server and to
  generally avoid mucking about with expanding and re-issuing
  existing certificates more than we have to.

*/

require 'config.php';
require 'logger.php';

if(LOG_LEVEL == 'all') { logMessage("Renewing certificates about to expire..."); }

$server_ip    = SERVER_IP;              // The public IPv4 address of this server. 
$certs_dir    = LE_CERTS_DIR;           // The directory which holds the directories for all the certificates.
$date_format  = CERT_DATETIME_FORMAT;   // Certificate date format, usually 'M d H:i:s Y e'.

// Loop through the directory of Certbot certificates.
$dir = new DirectoryIterator($certs_dir);
foreach ($dir as $domain) {
  // Make sure we're skipping '.' and '..'
  if ($domain->isDir() && !$domain->isDot()) {
    if(LOG_LEVEL == 'all') { logMessage("Checking $domain..."); }
    // Check and make sure DNS is still valid, otherwise it doesn't matter if the
    // certificate expires.
    //
    // Again, we check twice in case of multiple A records.
    if(gethostbyname($domain) == $server_ip && gethostbyname($domain) == $server_ip) {

      // Get information about the certificat - it's effective start and end dates.
      // Note: LegsEncrypt uses fullchain.pem but also generates cert.pem. When migrating
      // over from another system, one or more of these may not be available.
      //
      // You can use either file here. 
      $cert_info = shell_exec("openssl x509 -noout -dates -in " . LE_CERTS_DIR . "$domain/fullchain.pem  2>&1");
      if(!$cert_info) {
        logMessage("Can't read certificate info for $domain.");
      }

      // Split up the info and discard what we don't need to get the date as text.
      $lines = explode("\n", $cert_info);
      $text_date = str_replace('notAfter=', '', $lines[1]);

      // Convert it to a date object.
      $expiry_date = date_create_from_format("M d H:i:s Y e", $text_date);
      
      // Get tomorrow's date so we can see if the certificate expires before then.
      $current_date = new Datetime();
      $tomorrow = $current_date->modify('+1 day');

      // If it's expiring within the next 24 hours, renew the certificate.
      if($expiry_date <= $tomorrow) {
        logMessage("Renewing $domain.");
        $cert = shell_exec("certbot certonly -n -d $domain 2>&1");
        logMessage($cert);
      } else {
        if(LOG_LEVEL == 'all') { logMessage("$domain will not expire within 24hrs."); }
      }
    } else {
      logMessage("$domain has invalid DNS settings.");
    }
    if(LOG_LEVEL == 'all') { logMessage("Finished checking $domain."); }
  }
}
if(LOG_LEVEL == 'all') { logMessage("Finished renewing certificates about to expire..."); }
