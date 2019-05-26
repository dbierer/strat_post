<?php
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
$download  = TRUE;      // set FALSE if you don't want to download
$wipe      = TRUE;      // set FALSE if you don't want to truncate existing geoname table
$delimiter = "\t";      // change to "," if comma separated values
$fix       = NULL;      // anon function to fix discrepancies
$unzip     = 'unzip';   // change this to the unzip command for your OS
$except    = __DIR__ . '/exceptions.txt';

// database credentials
$dsn       = 'mysql:host =localhost;dbname=geonames';
$user      = 'test';
$pass      = 'password';
$opts      = [PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION];

// database table / fields
$fields = [
    'geoname' => [
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
    'post_code' => [
        'country_code',
        'postal_code',
        'place_name',
        'admin_name1',
        'admin_code1',
        'admin_name2',
        'admin_code2',
        'admin_name3',
        'admin_code3',
        'latitude',
        'longitude',
        'accuracy'
    ]
];

foreach ($fields as $table => $columns) {

    // init vars
    $count     = 0;
    $expected  = 0;
    $actual    = 0;
    $destZip   = __DIR__ . '/' . $table . '.zip';
    $destTxt   = __DIR__ . '/' . $table . '.txt';
    $unzipCmd  = $unzip . ' ' . $destZip;
    $except    = __DIR__ . '/' . $table . '_exceptions.txt';
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
            $pdo->exec('DELETE FROM `' . $table . '`');
        }

        // parse source file
        echo "\nParsing the Source File Lines into Database Table Rows...\n";
        $split = function ($line) use ($delimiter) { return explode($delimiter, trim($line)); };
        $srcObj  = new SplFileObject($destTxt, 'r');

        while ($line = $srcObj->fgets()) {
            $insert = $split(trim($line));
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
