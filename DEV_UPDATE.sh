#!/bin/bash
#FILE="/tmp/out.$$"
#GREP="/bin/grep"

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

# Check for Node module updates
(npm-check-updates -V || npm install -g npm-check-updates) && npm-check-updates -u

# Delete all and re-install everything
rm -fr node_modules && npm install -d