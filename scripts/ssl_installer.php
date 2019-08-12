<?php

/*

  IMPORTANT: Must be ran via sudo.

  Run: Every few hours.

  This is a very simple script that loops over files in a 'pending' directory
  (domains yet to be installed) and goes through a series of setup steps to
  install them:

    1. It validates the DNS configuration for the domain.
    2. It issues an SSL certificate for the domain via Certbot.
    3. If the certificate creation was successful, it moves the confit
       file to the 'enabled' directory.
    4. It then creates a symlink from the file in 'enabled' to the NGINX 
       'sites-enabled' directory.
    5. If at least one domain has been installed, it reloads the NGINX
       configuration files.

  NOTE: This is a change from the original script in that Certbot no longer
        modifies the config file to add the SSL directives. As such, the
        config files must contain this info when written to the server for
        later installation.

*/

require 'config.php';
require 'logger.php';

$pending_dir        = VHOST_PENDING_DIR;          // Your pending config files live here.
$enabled_dir        = VHOST_ENABLED_DIR;          // Your enabled config files live here.
$bad_dir            = VHOST_BAD_DOMAINS_DIR;      // Your enabled config files live here.
$nginx_enabled_dir  = NGINX_ENABLED_DIR;          // We create a symlink to them here.
$server_ip          = SERVER_IP;                  // The IP address to your server. 
$max_this_time      = MAX_INSTALLS_PER_EXECUTION; // The maximum number of certificates to install.

$count = 0; // Count the number of files we've moved so we know if we need to reload NGINX.

// Pass a command line argument to the script to re-run the ssame process again on
// config files in the 'bad domains' directory. Ideally, this should be ran daily,
// after certificates have renewed.

if(isset($argv[1])) {
  $second_chance = true;

  // Read from the bad domains directory instead of the pending directory.
  $pending_dir = $bad_dir;
} else {
  $second_chance = false;
}


if($second_chance) {
  if(LOG_LEVEL == 'all') { logMessage("Retrying previously failed vhosts..."); }
} else {
  if(LOG_LEVEL == 'all') { logMessage("Enabling pending vhosts..."); }
}

exec("pgrep certbot", $pids);
if(!empty($pids)) {
  logMessage("Certbot is still running... waiting a bit...");
} else {
  // Loop over all pending files.
  foreach(glob($pending_dir.'*.conf') as $file) {

    // Don't install if we've hti the max number of files to install. 
    if($count < $max_this_time) {
      if(LOG_LEVEL == 'all') { logMessage("Enabling $file..."); }

      // Generate the file name for the file we're outputting to the enabled directory.
      // Make the 'bad file' option, as well, in case we need it later.
      $output_file = str_replace($pending_dir, $enabled_dir, $file);
      $bad_file = str_replace($pending_dir, $bad_dir, $file);
      $domain = str_replace([$pending_dir, '.conf'], ['',''], $file);

      // Generate the name for the symlink.
      $link = str_replace($pending_dir, $nginx_enabled_dir, $file);

      if(gethostbyname($domain) == $server_ip && gethostbyname($domain) == $server_ip) {
        // Quietly issue and install the certificate in a non-interactive way.
        // We don't actually care much if this fails, we'll just try it again
        // next time. 
        if(LOG_LEVEL == 'all') { logMessage("Getting certificate for $domain..."); }

        $shell = shell_exec("certbot certonly -a webroot --webroot-path=/var/www/letsencrypt -m kelli@stickyfolios.com --agree-tos -d=$domain 2>&1");

        if(LOG_LEVEL == 'all') { logMessage($shell); }
        if(LOG_LEVEL == 'all') { logMessage("Done getting certificate for $domain."); }

        // Check to see if the certificate was issued successfully, or that it already
        // exists in a valid state.
        if(stristr($shell, 'Congratulations!') || stristr($shell, 'Certificate not yet due for renewal')) {
          // Move the file from pending to the 'enabled' directory.
          if(!rename($file, $output_file)) {
            logMessage("Unable to rename $file > $output_file");
          } else {
            logMessage("Enabled $output_file");
          }

          // Create symlink to NGINX 'sites-enabled' directory.
          if(!symlink($output_file, $link)) {
            logMessage("Unable to symlink $output_file > $link");
          } else {
            logMessage("Symlinked $output_file");
          }

          if(LOG_LEVEL == 'all') { logMessage("Done enabling $file."); }
        } else {
          // Woops, the certificate didn't get installed, for some reason. 
          logMessage("Certificate for $domain was not installed.");
          logMessage($shell);
          if(!$second_chance) {
            if(rename($file, $bad_file)) {
              if(LOG_LEVEL == 'all') { logMessage("Moved $domain to bad domains directory."); }
            } else {
              logMessage("Unable to rename $file > $bad_file");
            }
          }
        }

        $count++; // Increase count so the NGINX config gets reloaded.
      } else {
        // Domain doesn't have a valid DNS setting, so we didn't try to install 
        // its SSL certs. We moved it to the bad domains directory, instead.
        if($second_chance) {
          if(LOG_LEVEL == 'all') { logMessage("$domain still has invalid DNS settings."); }
        } else {
          if(LOG_LEVEL == 'all') { logMessage("$domain has invalid DNS settings."); }
          if(rename($file, $bad_file)) {
            if(LOG_LEVEL == 'all') { logMessage("Moved $domain to bad domains directory."); }
          } else {
            logMessage("Unable to rename $file > $bad_file");
          }
        }
      }
    } else {
      if(LOG_LEVEL == 'all') { logMessage('Skipping $file (limit reached).'); }
    }
  }

  if($second_chance) {
    if(LOG_LEVEL == 'all') { logMessage("Done retrying failed vhosts."); }
  } else {
    if(LOG_LEVEL == 'all') { logMessage("Done enabling pending vhosts."); }
  }

  // Reload NGINX as necessary.
  if($count > 0) {
    if(LOG_LEVEL == 'all') { logMessage("Reloading nginx config..."); }
    $log = shell_exec("sudo service nginx reload 2>&1");
    logMessage($log);
    if(LOG_LEVEL == 'all') { logMessage("Done reloading nginx config."); }
  }
}