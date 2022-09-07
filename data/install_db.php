<?php
// Usage:
// install_db.php [DOWNLOAD] [WIPE]

// installs the `geoname` and `post_code` tables in the `geonames` database
// see https://download.geonames.org/export/dump/readme.txt for more information


// *************************************************************************************
// data source (from https://download.geonames.org/export/dump/)
// *************************************************************************************

// cities > 15000 in population ~24200 entries
$src['geoname'] = 'https://download.geonames.org/export/dump/cities15000.zip';

// all cities (340M download!)
//$src['geoname'] = 'https://download.geonames.org/export/dump/allCountries.zip';


// *************************************************************************************
// data source (from https://download.geonames.org/export/zip/)
// *************************************************************************************
$src['post_code'] = 'https://download.geonames.org/export/zip/allCountries.zip';


// init vars
$download  = $_GET['download'] ?? $argv[1] ?? TRUE;      // set FALSE if you don't want to download
$wipe      = $_GET['wipe']     ?? $argv[2] ?? TRUE;      // set FALSE if you don't want to truncate existing geoname table
$delimiter = "\t";      // change to "," if comma separated values
$fix       = NULL;      // anon function to fix discrepancies
$unzip     = 'unzip';   // change this to the unzip command for your OS
$except    = __DIR__ . '/exceptions.txt';

// database credentials
$dsn       = 'mysql:host =localhost;dbname=strat_post';
$user      = 'strat_post';
$pass      = 'password';
$opts      = [PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION];

// create SQL
$create = <<<EOT
CREATE TABLE IF NOT EXISTS `post_code` (
 `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
 `country` char(2) DEFAULT NULL,
 `postcode` varchar(20) DEFAULT NULL,
 `city` varchar(180) DEFAULT NULL,
 `state_prov_name` varchar(100) DEFAULT NULL,
 `state_prov_code` varchar(20) DEFAULT NULL,
 `locality_name` varchar(100) DEFAULT NULL,
 `locality_code` varchar(20) DEFAULT NULL,
 `region_name` varchar(100) DEFAULT NULL,
 `region_code` varchar(20) DEFAULT NULL,
 `latitude` decimal(10,4) DEFAULT NULL,
 `longitude` decimal(10,4) DEFAULT NULL,
 `accuracy` char(2) DEFAULT NULL,
 PRIMARY KEY (`id`),
 FULLTEXT KEY `post_code_country_code_idx` (`country`),
 FULLTEXT KEY `post_code_place_name_idx` (`city`)
) ENGINE=InnoDB AUTO_INCREMENT=3568104 DEFAULT CHARSET=utf8
EOT;

// database table / fields
$fields = [
    /*
    'geoname' => [
        'fields' => [
            'geonameid',
            'name',
            'asciiname',
            'alternatenames',
            'latitude',
            'longitude',
            'feature_class',
            'feature_code',
            'country_code',
            'cc2',
            'admin1_code',
            'admin2_code',
            'admin3_code',
            'admin4_code',
            'population',
            'elevation',
            'dem',
            'timezone',
            'modification_date'
        ],
    ],
    */
    'post_code' => [
        'fields' => [
            // db col            geonames field
            'id'              => 'post_code_id',
            'country'         => 'country_code',
            'postcode'        => 'postal_code',
            'city'            => 'place_name',
            'state_prov_name' => 'admin_name1',
            'state_prov_code' => 'admin_code1',
            'locality_name'   => 'admin_name2',
            'locality_code'   => 'admin_code2',
            'region_name'     => 'admin_name3',
            'region_code'     => 'admin_code3',
            'latitude'        => 'latitude',
            'longitude'       => 'longitude',
            'accuracy'        => 'accuracy'
        ],
        'create' => $create,
    ]
];

foreach ($fields as $table => $items) {

    // init vars
    $count     = 0;
    $expected  = 0;
    $actual    = 0;
    $destZip   = __DIR__ . '/' . $table . '.zip';
    $destTxt   = __DIR__ . '/' . $table . '.txt';
    $unzipCmd  = $unzip . ' ' . $destZip;
    $except    = __DIR__ . '/' . $table . '_exceptions.txt';
    $columns   = array_keys($items['fields']);
    try {


        // download the file
        if ($download) {
            echo "\nDownloading the Source File for $table ...\n";
            $srcFh   = fopen($src[$table], 'r');
            $destObj = new SplFileObject($destZip, 'w');
            while($bytes = fread($srcFh, 4096)) $destObj->fwrite($bytes);
            fclose($srcFh);
            unset($destObj);
            // unzip
            echo "Unzipping Source File ... Please Wait\n";
            $result = system(escapeshellcmd($unzipCmd));
            echo PHP_EOL;
            // change filename of unzipped file
            $old = __DIR__ . '/' . substr(basename($src[$table]), 0, -3) . 'txt';
            rename($old, $destTxt);
        }

        // setup database
        $pdo = new PDO($dsn, $user, $pass, $opts);
        $sql = 'INSERT INTO `' . $table . '` (`'
             . implode('`,`', $columns)
             . '`) VALUES (' . str_repeat('?,', count($columns)) . ')';
        $sql = str_replace([',,',',)'], ['',')'], $sql);
        echo $sql . PHP_EOL;
        $stmt = $pdo->prepare($sql);

        // open exceptions file
        $exObj = new SplFileObject($except, 'w');

        // truncate table
        if ($wipe) {
            //$pdo->exec('IF EXISTS ' . $table . ' DELETE FROM `' . $table . '`');
            $pdo->exec($items['create']);
        }

        // parse source file
        $srcObj  = new SplFileObject($destTxt, 'r');

        while ($line = $srcObj->fgetcsv($delimiter)) {
            $insert = [];
            foreach ($line as $tmp)
                $insert[] = trim($tmp);
            if ($expected === 0) $count = count($insert);
            echo $expected++ . ' ';
            if (count($insert) !== $count) {
                if ($fix) {
                    $data = $fix($headers, $insert);
                    if ($stmt->execute($insert)) {
                        $actual++;
                    }
                } else {
                    // write line to exclusion file
                    $exObj->fwrite(implode(',', $insert) . PHP_EOL);
                }
            } else {
                try {
                    if ($stmt->execute($insert)) {
                        $actual++;
                    }
                } catch (Exception $e) {
                    $exObj->fwrite($e->getMessage() . PHP_EOL);
                }
            }
        }

    } catch (Throwable $e) {

        echo get_class($e) . ':' . $e->getMessage();

    } finally {

        echo "\nWe're done with $table\n";
        echo "Expected: $expected\n";
        echo "Actual:   $actual\n";

    }
}
