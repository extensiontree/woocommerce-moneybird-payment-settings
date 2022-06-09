#!/bin/bash
# This script builds woocommerce-moneybird-payment-settings.zip

EXTNAME="woocommerce-moneybird-payment-settings"
EXTROOT=$PWD

# Check version tagging
if [ $# -eq 0 ]
then
    MOST_RECENT_TAG=`git describe --tags --abbrev=0 | cut -c2-`
    if [ -z "$MOST_RECENT_TAG" ]
    then
        echo "No tag found"
        exit 1
    elif grep -Fq "Version: $MOST_RECENT_TAG" ./$EXTNAME.php
    then
        echo "- Latest version tag found in plugin header!"
    else
        echo "Latest version tag NOT found in plugin header!"
        exit 1
    fi
fi

# Clean temporary files
rm -f ./*~

# Collect files
rm -f $EXTROOT/$EXTNAME.zip
rm -f -r /tmp/$EXTNAME
mkdir /tmp/$EXTNAME

cp ./*.md /tmp/$EXTNAME/
cp ./*.php /tmp/$EXTNAME/

# Create zip
cd /tmp; zip -r -q $EXTROOT/$EXTNAME.zip $EXTNAME

# Clean up
rm -f -r /tmp/$EXTNAME

echo "All done."
