Noonan
====

Noonan is a small set of PHP scripts for managing an NGINX-based SSL proxy server.

Requirements:
----
  
* NGINX installed
* Certbot installed
* PHP 5.3+ installed
* You will need ports 80 and 443 open on your proxy server
* The scripts need to be able to create, read, and write to subfolders of a location you specify
* You will need to be able to run PHP scripts unattended via sudo

Setup:
----

* Rename `scripts/example.config.php` to `scripts/config.php`, open it, and edit the values within.
* Run `php setup.php` and follow the instructions.
* Add the recommended jobs to your crontab (tweaking times and frequences as desired).
* Start publishing config files to your vhost pending directory.

Please see the individaul scripts for their documentation.

Sample HTTPS Config file:
----

Files you publish to the vhost pending directory should be configured for HTTPS and will be used to proxy to your destination server.

Certbot will NOT modify these config files, so it's important to go ahead and set them up with the paths LetsEncrypt will use.

Fortunately, these are predictable!

Below is a sample vhost configuration file:

    server {
      listen    80;
      listen    443 ssl;
      server_name your.domain.com;
      include proxy_params;

      location / {
        proxy_pass http://your-destination-ip$request_uri;
      }
      ssl_certificate /etc/letsencrypt/live/your.domain.com/fullchain.pem;
      ssl_certificate_key /etc/letsencrypt/live/your.domain.com/privkey.pem;
    }

    server {
      listen    80;
      server_name your.domain.com;
      location / {
        return 301 https://$host$request_uri;
      }
    }


More advanced configurations can be created by configuring `/etc/nginx/proxy_params` if necessary/desired. However, NGINX's defaults will work in most cases.

NGINX Default Vhost Configuration:
----

Please note that Noonan only handles SSL configurations and only installs vhost files for SSL configurations.

In the interest of not breaking things before SSL certificates are issued, and for also hosting sites over HTTP, you should configure a catch-all vhost in NGINX.

Since we're just forwarding the request on to nother server, this prevents the need to create vhost entries for sites that will only be served over HTTP, removing a lot of clutter and complexity.

Below is a sample:

    # /etc/nginx/sites-enabled/default

    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    include proxy_params;
    location / {
      proxy_pass http://your-destination-ip$request_uri;
    }
  }    

Please note that you **must** include the `default_server` declrations above in the catch-all vhost, or NGINX may attempt to do strange things, like auto-redirect to HTTPS and then serve the wrong certificate.

Important Security Notes:
----

Please secure your server. You are running PHP scripts unattended via sudo. Please don't cause a tear in the fabric of reality.

At the very least, you should:

* Move SSH off of port 22.
* Disable password-based login.
* Publish config files over SFTP using an RSA key pair.
* Lock down ports you don't need.
* Disable PHP in the Nginx. You won't need it there.
* Run nothing else on this server - the proxy server is meant to act as a lightweight stand-alone. 
