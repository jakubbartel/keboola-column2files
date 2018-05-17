<?php

$f = fopen("data_1/in/tables/purchases.csv", "w+");

$lines = 250; // 250000;
$uniques = 10; // 5000;

foreach(range(0, $lines) as $i) {
    $line = sprintf("%s,%s,%s,%s\n", rand(0, $uniques), "\"ijwvfhnfwv\"", rand(2726272876, 7826827679279279), "\"wkjvnwv\"");
    fwrite($f, $line);
}

fclose($f);
