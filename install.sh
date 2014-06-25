#!/bin/bash

if [ ! -f composer.phar ]
then
  echo "Installing Composer..."
  curl -sS https://getcomposer.org/installer | php
else
  echo "Composer alredy installed"
fi

if [ -d vendor ]
then
  php composer.phar update
else
  php composer.phar install
fi

