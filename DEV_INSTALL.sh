#!/bin/bash
# FILE="/tmp/out.$$"
# GREP="/bin/grep"

# Make sure only root can run our script
if [ "$(id -u)" == "0" ]; then
   echo "This script must be run as a normal user" 1>&2
   exit 1
fi

# Make sure this can only run outside VirtualBox / VVV
if [ "$(dmesg | grep VirtualBox || echo '1')" != '1' ]; then
	echo "This script must be run outside VirtualBox / VVV" 1>&2
	exit 1
fi

# Install Homebrew (needed for gettext & node)
which brew || ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"

# Install node.js
npm -v || sudo brew install node

# Install bower
bower -v || sudo npm install -g bower

# Install VASSH for sending commands to VVV
vassh --help || brew install vassh

# Install pear if not installed
pear list || ( curl -O http://pear.php.net/go-pear.phar && sudo php -d detect_unicode=0 go-pear.phar && sudo rm go-pear.phar )

# Install Ruby Sass since we need it for compiling sass (gulp-ruby-sass)
gem list -i sass || sudo gem install sass

# Install Gulp
gulp -v || sudo npm install -g gulp

# Install all the node packages we need for development
npm install -d

# Install PHP Code Sniffer
pear list PHP_CodeSniffer || sudo pear install PHP_CodeSniffer

# Install WordPress Standards for PHPCS
rm -fR wpcs
git clone -b master https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git wpcs && sudo cp -r wpcs/WordPress* $(pear config-get php_dir)/PHP/CodeSniffer/Standards && rm -fR wpcs && phpcs -i

# Sometimes PHPCS fails, make it work: from http://viastudio.com/configure-php-codesniffer-for-mac-os-x/
phpcs -i || ( sudo mkdir -p /Library/Server/Web/Config/php && sudo touch /Library/Server/Web/Config/php/local.ini && echo 'include_path = ".:'`pear config-get php_dir`'"' | sudo tee -a /Library/Server/Web/Config/php/local.ini )

# Install SCP-2
npm install gulp-scp2 --save-dev

# Install RSync
npm install gulp-rsync --save-dev

# Install gettext for tranlations
brew list gettext || brew install gettext