#!/usr/bin/env bash

if (( "$#" != 1 ))
then
    echo "Git range must be provided"
    exit 1
fi


IFS='
'
ALL_CHANGED_FILES=$(git diff --name-only --diff-filter=ACMRTUXB "$1");
PKG_PHP_CHANGED_FILES=$(echo "$ALL_CHANGED_FILES" | grep -E "^pkg\/" | grep -E ".*?\.php$");

echo "$ALL_PHP_CHANGED_FILES";
#echo "$PKG_PHP_CHANGED_FILES";
