#!/bin/sh
/sbin/start-stop-daemon -S -x /home/pi/yasdi/islandlogger/logger > /home/pi/yasdi/islandlogger/out 2>/home/pi/yasdi/islandlogger/err &
