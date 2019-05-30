#!/usr/bin/sh
DB=geonames
# need to set up a for loop for post_code_* == fn
mysqlimport -uvagrant -pvagrant DB fn
