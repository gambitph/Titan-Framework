#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-master}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=/tmp/wordpress/

set -ex
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};" --user="${DB_USER}" --password="${DB_PASS}"

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	# Original: Always do wget
	# wget -nv -O /tmp/wordpress.tar.gz http://wordpress.org/${ARCHIVE_NAME}.tar.gz
	# tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
	
	# Modified: Only do wget if it hasn't been downloaded before
	CURR_DIR=$(pwd)
	cd /tmp
	wget --timestamping -nv http://wordpress.org/${ARCHIVE_NAME}.tar.gz
	cd ${CURR_DIR}
	tar --strip-components=1 -zxmf /tmp/${ARCHIVE_NAME}.tar.gz -C $WP_CORE_DIR
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i ""'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR
	# continue even if failed
	svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ || true

	# Original: Always do wget
	# wget -nv -O wp-tests-config.php http://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php
	
	# Modified: Only do wget if it hasn't been downloaded before
	wget --timestamping -nv http://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php
	mv wp-tests-config-sample.php wp-tests-config.php
	
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_db() {
	mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};" --user="${DB_USER}" --password="${DB_PASS}"
}

install_wp
install_test_suite
install_db