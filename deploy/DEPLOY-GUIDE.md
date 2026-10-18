# سایټ آنلاین کول

دا لارښود دوه لارې لري: **VPS** (خپل سرور، غوره لاره) او **cPanel** (ګډ هوستینګ).
کمانډونه د انګلیسي وي، لکه څنګه چې دي یې کاپي کړئ.

---

## ۱) مخکې له پیل (په خپل کمپیوټر کې)

1. ټیسټونه وچلوئ، ټول باید سبز وي:
   ```
   php artisan test
   ```
2. د ډیزاین فایلونه جوړ کړئ (په سرور کې Node نه غواړو):
   ```
   npm run build
   ```
3. وګورئ چې `public/hot` فایل نشته. که وي، ړنګ یې کړئ. دا هغه نښه ده چې وايي «د پرمختیا سرور چالان دی»، او په ژوندي سایټ کې سایټ ماتوي.
4. دا شیان سرور ته **مه** پورته کوئ: `.env`، `node_modules`، `.git`، او `public/hot`.
   `vendor` هم نه (سرور پخپله جوړوي)، مګر په cPanel کې باید پورته شي (لاندې وګورئ).

---

## ۲) VPS لاره (Ubuntu)

### د سرور چمتو کول
```
sudo apt update
sudo apt install -y nginx mysql-server php-cli php-fpm php-mysql php-mbstring php-xml php-curl php-zip php-bcmath unzip composer
```
PHP نسخه باید د `composer.json` غوښتنې پوره کړي (ستاسو په کمپیوټر کې `php -v` وګورئ).

### ډېټابېس (د root پر ځای جلا کارن)
```
sudo mysql
```
په هغه کې:
```
CREATE DATABASE mutamireen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mutamireen'@'localhost' IDENTIFIED BY 'یو-اوږد-قوي-پاسورډ';
GRANT ALL PRIVILEGES ON mutamireen.* TO 'mutamireen'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### کوډ
کوډ `/var/www/mutamireen` ته پورته کړئ (په `scp`، `rsync`، یا `git`). بیا:
```
cd /var/www/mutamireen
composer install --no-dev --optimize-autoloader
cp deploy/.env.production.example .env
nano .env
```
په `.env` کې دا ډک کړئ: `APP_URL` (ستاسو ډومین، https سره) او `DB_PASSWORD`.
که پاسورډ کې `$` یا `#` وي، د یو کوټ ('...') ترمنځ یې ولیکئ.

```
php artisan key:generate
php artisan migrate --force
php artisan admin:create your@email.com
```
وروستی کمانډ د مدیر پاسورډ دوه ځلې غواړي. پاسورډ په سکرین نه ښکاري، او هېڅ ځای نه لیکل کېږي.
**مهم:** په سرور کې `php artisan db:seed` مه چلوئ. هوټلونه او اطاقې د سایټ دننه جوړېږي.

### حق‌السیستم او ساتنه
```
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R u+rwX,g+rwX storage bootstrap/cache
sudo chown root:www-data .env
sudo chmod 640 .env
php artisan optimize
```

### Nginx او HTTPS
1. `deploy/nginx.conf.example` د `/etc/nginx/sites-available/mutamireen` ته کاپي کړئ، او `example.com` په خپل ډومین بدل کړئ.
2. فعال یې کړئ:
   ```
   sudo ln -s /etc/nginx/sites-available/mutamireen /etc/nginx/sites-enabled/
   sudo nginx -t && sudo systemctl reload nginx
   ```
3. HTTPS (وړیا):
   ```
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d example.com
   ```

### د سرور ساتنه
```
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo apt install -y fail2ban unattended-upgrades
```
په SSH کې یوازې کیلي (key) سره ننوځئ، او پاسورډ سره ننوتل بند کړئ.

---

## ۳) cPanel لاره (ګډ هوستینګ)

1. په cPanel کې **MySQL Databases** ته ورشئ، یو ډېټابېس او کارن جوړ کړئ، او کارن ته ټول حقوق ورکړئ.
2. په خپل کمپیوټر کې `composer install --no-dev --optimize-autoloader` وچلوئ، او ټول پروژه (د `vendor` او `public/build` سره) zip کړئ. د `.env`، `node_modules` او `public/hot` نه پرته.
3. File Manager کې zip د **`public_html` نه بهر** پورته کړئ (مثلاً `/home/user/mutamireen`) او Extract یې کړئ.
4. د ډومین **Document Root** په `/home/user/mutamireen/public` بدل کړئ (Domains ← Manage). **دا ډېره مهمه ده**: که ریښه `public` نه وي، `.env` د انټرنېټ نه لیدل کېدای شي.
5. `deploy/.env.production.example` د `.env` په نوم کاپي کړئ او ډک یې کړئ.
6. په Terminal (که شته) کې: `php artisan key:generate`، `php artisan migrate --force`، `php artisan admin:create your@email.com`، `php artisan optimize`.
   که Terminal نشته، له هوستینګ څخه یې وغواړئ، یا د VPS لاره وکاروئ.
7. HTTPS: په cPanel کې **SSL/TLS Status** ← AutoSSL.

---

## ۴) Backup (ډېره مهمه)

1. د backup کارن لپاره د ډېټابېس پاسورډ په یوه محرم فایل کې (نه په script کې):
   ```
   nano ~/.my.cnf
   ```
   ```
   [client]
   user=mutamireen
   password='ستاسو-پاسورډ'
   ```
   ```
   chmod 600 ~/.my.cnf
   ```
2. لاسي ازموینه:
   ```
   /var/www/mutamireen/deploy/backup.sh
   ```
3. هره شپه ۲ بجې:
   ```
   crontab -e
   ```
   ```
   0 2 * * * /var/www/mutamireen/deploy/backup.sh
   ```
4. backup فایلونه **بل ځای** هم کاپي کړئ (بل کمپیوټر، یا د بل سرور). که سرور ورک شي، backup هم ورسره ورکېږي.
5. هره میاشت یو ځل **بېرته راګرځول** وازمایئ:
   ```
   gunzip < /var/backups/mutamireen/FILE.sql.gz | mysql mutamireen_restore_test
   ```
   backup چې هېڅکله نه وي ازمول شوی، backup نه دی.

---

## ۵) وروسته تازه کول

نوی کوډ پورته کړئ، بیا په سرور کې:
```
cd /var/www/mutamireen
./deploy/update.sh
```
دا لومړی backup اخلي، سایټ لنډ وخت «ساتنه» حالت ته ځي، بدلونونه ایږدي، او بیا خلاصېږي.

---

## ۶) د آنلاین کېدو وروستی لیست

- [ ] `APP_DEBUG=false` او `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true` او سایټ په `https://` خلاصېږي
- [ ] ریښتینی مدیر جوړ شوی (`admin:create`)، او پاسورډ یې قوي دی
- [ ] `https://ستاسو-ډومین/.env` **404** ښيي (نه د فایل منځپانګه)
- [ ] د ازمایښت حسابونه (viewer@test…) ړنګ شوي
- [ ] Backup چلېږي، او یو ځل بېرته راګرځول شوی
- [ ] په Activity log کې خپل ننوتل ولیدئ
- [ ] `php artisan test` په کمپیوټر کې سبز دی

## که څه خراب شو

- سپین یا ۵۰۰ پاڼه: `storage/logs/laravel-DATE.log` وګورئ (وروستۍ کربنې).
- بدلونونه نه ښکاري: `php artisan optimize:clear` او بیا `php artisan optimize`.
- `Permission denied`: د `storage` او `bootstrap/cache` حق‌السیستم بیا وټاکئ.
