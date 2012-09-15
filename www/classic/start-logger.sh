#!/bin/sh
/sbin/start-stop-daemon -S -x /usr/bin/php /var/www/classic/classiclogger.php &
