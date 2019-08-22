# Post Code / Geo Cities API

## Setup
* Create database `geonames`
* Create tables `geoname` and `post_code`
  * SQL dumps are located in `/data` directory
* Modify the database credentials in `/data/install_db.php` to suit your installation
* Run `/data/install_db.php` to import data from source files into database

## Running the API
* If running locally on your own computer:
  * Make sure you have PHP 7.* installed
  * Change to the '/' directory for this project
  * Run this command: `php -S localhost:9999 -t public`
  * From any REST client create a GET request to: `http://locahost/city/XXX` or `http://locahost:9999/postcode/XXX` where `XXX` is the partial or complete city name or post code
* If running on a web server:
  * Create a virtual host definition according to the configuration for your web server
  * Set the document root to the `/public` directory
  * If running Apache, make sure the `.htaccess` file with rewrite rules is in the `/public` directory of the project
  * If running Nginx use the equivalent rewrite rules in the main config file

## Table Descriptions
### Geoname Table Fields:
| Field             | Description |
| ------------------|-------------------------------- |
| geonameid         | integer id of record in geonames database |
| name              | name of geographical point (utf8) varchar(200) |
| asciiname         | name of geographical point in plain ascii characters, varchar(200) |
| alternatenames    | alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000) |
| latitude          | latitude in decimal degrees (wgs84) |
| longitude         | longitude in decimal degrees (wgs84) |
| feature_class     | see http://www.geonames.org/export/codes.html, char(1) |
| feature_code      | see http://www.geonames.org/export/codes.html, varchar(10) |
| country_code      | ISO-3166 2-letter country code, 2 characters |
| cc2               | alternate country codes, comma separated, ISO-3166 2-letter country code, 200 characters |
| admin1_code       | fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20) |
| admin2_code       | code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) |
| admin3_code       | code for third level administrative division, varchar(20) |
| admin4_code       | code for fourth level administrative division, varchar(20) |
| population        | bigint (8 byte int) |
| elevation         | in meters, integer |
| dem               | digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat. |
| timezone          | the iana timezone id (see file timeZone.txt) varchar(40) |
| modification_date | date of last modification in yyyy-MM-dd format |
| postal_code       | varchar(20) |

### Postcode Table Fields:
| Field             | Description |
| ------------------|---------------------------------------- |
| country_code      | iso country code, 2 characters |
| postal_code       | varchar(20) |
| place_name        | varchar(180) |
| admin_name1       | 1. order subdivision (state) varchar(100) |
| admin_code1       | 1. order subdivision (state) varchar(20) |
| admin_name2       | 2. order subdivision (county/province) varchar(100) |
| admin_code2       | 2. order subdivision (county/province) varchar(20) |
| admin_name3       | 3. order subdivision (community) varchar(100) |
| admin_code3       | 3. order subdivision (community) varchar(20) |
| latitude          | estimated latitude (wgs84) |
| longitude         | estimated longitude (wgs84) |
| accuracy          | accuracy of lat/lng from 1=estimated, 4=geonameid, 6=centroid of addresses or shape |
