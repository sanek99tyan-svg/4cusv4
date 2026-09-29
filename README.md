# 4cus CMS

Полноценная PHP/MySQL версия сайта 4cus на базе дизайна `4cusv4`.

## Что добавлено

- PHP-движок без фреймворка.
- MySQL.
- Авторизация администратора.
- Модульная админ-панель.
- Редактирование RU/EN текстов.
- Включение/отключение блоков и их порядок.
- Галерея: добавление, редактирование, удаление, загрузка изображений.
- SEO-настройки.
- Сохранение заявок формы в базу.
- Отдельная страница 404.
- Исходный дизайн и анимации сохранены в `template.html`.
- `parth.html` оставлен как отдельная страница.

## Требования

- PHP 8.1+
- PHP-FPM
- MySQL / MariaDB
- Nginx
- PHP extensions: pdo_mysql, mbstring, fileinfo

## Развёртывание на 4cus.team

В каталоге сайта:

```bash
cd /var/www/4cus.team
git pull
```

Установите PHP и расширения, если их нет:

```bash
sudo apt update
sudo apt install php-fpm php-mysql php-mbstring
```

Создайте БД и пользователя MySQL:

```sql
CREATE DATABASE fourcus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fourcus'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON fourcus.* TO 'fourcus'@'localhost';
FLUSH PRIVILEGES;
```

Используйте `nginx.4cus.team.conf.example` как основу конфига Nginx. Проверьте фактический PHP-FPM socket:

```bash
ls /run/php/
```

После изменения Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Затем откройте:

```
https://4cus.team/install.php
```

Введите данные MySQL и создайте администратора.

После успешной установки удалите установщик:

```bash
rm /var/www/4cus.team/install.php
rm /var/www/4cus.team/install.sql
```

Админка:

```
https://4cus.team/admin/
```

## Права на загрузки

```bash
sudo mkdir -p /var/www/4cus.team/uploads/gallery
sudo chown -R www-data:www-data /var/www/4cus.team/uploads
sudo chmod -R 775 /var/www/4cus.team/uploads
```

Если сайт редактируется через SSH под отдельным пользователем, настройте общую группу вместо выдачи широких прав.
