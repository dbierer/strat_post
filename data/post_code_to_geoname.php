<?php
// copies post code from the `post_code` table into the `geoname` table

// database credentials
$dsn       = 'mysql:host =localhost;dbname=geonames';
$user      = 'test';
$pass      = 'password';
$opts      = [PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION];

// init vars
$expected  = 0;
$actual    = 0;
$except    = __DIR__ . '/post_code_exceptions.txt';
$exFileObj = new SplFileObject($except, 'w');

try {

    // setup database
    $pdo = new PDO($dsn, $user, $pass, $opts);

    // truncate join table
    $pdo->exec('DELETE FROM `geoname_post_codes`');

    // insert into geoname table
    $insSql = 'INSERT INTO `geoname_post_codes` (`geonameid`,`post_code`) VALUES (:geonameid, :post_code)';
    $insStmt = $pdo->prepare($insSql);

    // lookup post code
    $postSql = 'SELECT `post_code_id`,`postal_code` FROM `post_code` '
            . 'WHERE `place_name` = :name '
            . 'AND `country_code` = :country_code '
            . 'AND `admin_code1` LIKE :admin1_code ';
    $postStmt = $pdo->prepare($postSql);

    // geoname query
    $geoStmt = $pdo->query("SELECT `geonameid`,`name`,`country_code`,`admin1_code` FROM `geoname`");

    echo "\nProcessing:\n";
    while ($row = $geoStmt->fetch(PDO::FETCH_ASSOC)) {
        $expected++;
        echo $row['name'] . ' ... ';
        $result = $postStmt->execute([
            'name'         => $row['name'],
            'country_code' => $row['country_code'],
            'admin1_code'  => $row['admin1_code']
        ]);
        try {
            if ($result) {
                $iteration = $postStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($iteration && count($iteration)) {
                    $post_code_list = [];
                    foreach($iteration as $item) {
                        $insStmt->execute(['post_code' => $item['postal_code'], 'geonameid' => $row['geonameid']]);
                    }
                    $actual++;
                }
            } else {
                $codes = [];
                $exFileObj->fwrite($row['name'] . PHP_EOL);
            }
        } catch (Throwable $e) {
            echo __LINE__ . ':' . get_class($e) . ':' . $e->getMessage() . PHP_EOL;
        }
    }

} catch (Throwable $e) {

    echo __LINE__ . ':' . get_class($e) . ':' . $e->getMessage() . PHP_EOL;

} finally {

    echo "\nWe're done\n";
    echo "Expected: $expected\n";
    echo "Actual:   $actual\n";

}
