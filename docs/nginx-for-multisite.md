### Steps to Create Nginx Configuration for Subdomain Support

1. **Set the `DOCKER_CONF_NGINX_PATH` Environment Variable**:
    Set the `DOCKER_CONF_NGINX_PATH` environment variable to the path where your custom Nginx configuration file will be located.

    ```sh
    export DOCKER_CONF_NGINX_PATH=/path/to/your/nginx/conf
    ```

2. **Create the Nginx Configuration File**:
    Create a new Nginx configuration file at the path specified by `DOCKER_CONF_NGINX_PATH`. The configuration should include the necessary settings to support subdomains.

    Example Nginx Configuration for Subdomain Support:

    ```nginx
    server {
         listen 80;
         listen 443 ssl;

         server_name *.example.com;

         root /var/www/html;
         index index.php;

         access_log /var/log/nginx/access.log;
         error_log /var/log/nginx/error.log;

         client_max_body_size 128M;

         ssl_certificate     /etc/nginx/certs/cert.crt;
         ssl_certificate_key /etc/nginx/certs/cert.key;

         location / {
              try_files $uri $uri/ /index.php?$args;
         }

         location ~ \.php$ {
              try_files $uri =404;
              fastcgi_split_path_info ^(.+\.php)(/.+)$;
              fastcgi_pass php:9000;
              fastcgi_index index.php;
              include fastcgi_params;
              fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
              fastcgi_param PATH_INFO $fastcgi_path_info;
         }

         location ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {
              expires 24h;
              log_not_found off;
         }

         location ^~ /blogs.dir {
              internal;
              alias /var/www/html/wp-content/blogs.dir;
              access_log off;
              log_not_found off;
              expires max;
         }

         if (!-e $request_filename) {
              rewrite /wp-admin$ $scheme://$host$uri/ permanent;
              rewrite ^/[_0-9a-zA-Z-]+(/wp-.*) $1 last;
              rewrite ^/[_0-9a-zA-Z-]+(/.*\.php)$ $1 last;
         }
    }
    ```