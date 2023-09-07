#! /usr/bin/bash

# Check if the user has provided two arguments
if [ $# -ne 2 ]; then
  echo "Usage: php [old_version] [new_version]"
  exit 1
fi

# Get the old and new PHP versions
old_version=$1
new_version=$2

# Check if the old PHP version is installed
if ! command -v php$old_version >/dev/null; then
  echo "The old PHP version [$old_version] is not installed."
  exit 1
fi

# Check if the new PHP version is installed
if ! command -v php$new_version >/dev/null; then
  echo "The new PHP version [$new_version] is not installed."
  exit 1
fi

# Switch to the new PHP version
echo "Switching to PHP version [$new_version]..."
sudo a2dismod php$old_version
sudo a2enmod php$new_version

# Restart Apache
echo "Restarting Apache..."
sudo service apache2 restart

sudo update-alternatives --set php /usr/bin/php$new_version

# Success message
echo "PHP version has been successfully switched to [$new_version]."
