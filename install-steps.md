# change the cli install path to point to my fork

sudo wget -q https://raw.githubusercontent.com/hekigan/raspap-webgui/master/installers/raspbian.sh -O /tmp/raspap && bash /tmp/raspap


# create nginx conf

create file in:
`/etc/nginx/sites-available/raspap`

and then symlink in:
`/etc/nginx/sites-enabled/`

```
server {
        listen   3000;
        listen   [::]:3000;

        root /var/raspap/html;
        index index.php index.html index.htm;
        server_name localhost;

        location / {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range';
            add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range';

            index index.php index.phtml index.html index.htm;
            try_files $uri $uri/ /index.html;
        }
        location ~ \.php$ {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range';
            add_header 'Access-Control-Expose-Headers' 'Content-Length,Content-Range';
            
            root /var/raspap/html;
            fastcgi_pass unix:/run/php/php7.0-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root/$fastcgi_script_name;
                        fastcgi_param REMOTE_USER $remote_user;
            auth_basic "Off";
            #auth_basic_user_file /usr/local/.htpasswd;
            }
        location ~ /\.ht {
                deny all;
        }
}

```

# dashboard

page credentials:

- login: admin
- password: secret