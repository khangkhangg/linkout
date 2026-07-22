# LinkOut — one-time server provisioning (debian-2gb-didudi)

## 0. DNS (manual, at the DNS provider)
A record: linkout.didudi.com -> <Hetzner IPv4>  (same IP as didudi.com)

## 1. Database
    mysql -e "CREATE DATABASE linkout CHARACTER SET utf8mb4;
      CREATE USER 'linkout'@'localhost' IDENTIFIED BY '<GENERATE-PW>';
      GRANT ALL ON linkout.* TO 'linkout'@'localhost';"

## 2. App dir + config
    mkdir -p /var/www/linkout
    # config.local.php (0640, www-data) on the server:
    <?php
    return [
        'db_dsn'   => 'mysql:host=127.0.0.1;dbname=linkout;charset=utf8mb4',
        'db_user'  => 'linkout',
        'db_pass'  => '<GENERATE-PW>',
        'base_url' => 'https://linkout.didudi.com',
        'env'      => 'prod',
    ];

## 3. nginx vhost /etc/nginx/sites-available/linkout.didudi.com
    server {
        listen 80;
        server_name linkout.didudi.com;
        root /var/www/linkout/public;
        index index.php;
        location / { try_files $uri /index.php$is_args$args; }
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        }
        location ~ /\. { deny all; }
    }
    ln -s ../sites-available/linkout.didudi.com /etc/nginx/sites-enabled/
    nginx -t && systemctl reload nginx

## 4. TLS
    certbot --nginx -d linkout.didudi.com

## 5. Outbound mail
    # Check what already sends mail on this box:
    which sendmail msmtp; ls /etc/msmtprc /etc/exim4 2>/dev/null
    php -r "var_dump(mail('dkhang@gmail.com','linkout mail test','it works'));"
    # If false / nothing arrives: apt install msmtp-mta, configure /etc/msmtprc
    # with the same SMTP account that sends for didudi.com, then re-test.

## 6. First deploy + admin
    ./deploy.sh          # from the Mac
    # then on the server after signing up through the site:
    mysql linkout -e "UPDATE users SET role='admin' WHERE email='dkhang@gmail.com'"
