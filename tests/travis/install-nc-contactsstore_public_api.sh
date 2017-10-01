#!/bin/bash -x
# set up postgresql
createuser -U travis -s oc_autotest
# set up mysql
mysql -e 'create database oc_autotest;'
mysql -u root -e "CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY '';"
mysql -u root -e "grant all on oc_autotest.* to 'oc_autotest'@'localhost';"
# install nextcloud
cd ..
#ncdev setup core --dir owncloud --branch $BRANCH --no-history
git clone https://github.com/nextcloud/server.git --recursive --depth 1 -b $BRANCH nextcloud
mv jsxc.nextcloud nextcloud/apps/ojsxc
phpenv config-add nextcloud/apps/ojsxc/tests/travis/php.ini
cd nextcloud
./occ maintenance:install --database-name oc_autotest --database-user oc_autotest --database-pass --admin-user admin --admin-pass admin --database $DB
./occ app:enable ojsxc
cd apps/ojsxc
