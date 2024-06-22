<?php

declare(strict_types=1);
//PHP Fatal error:  Uncaught Error: Call to undefined function yaml_parse() => pecl install yaml

$yaml1Path = $argv[1];
$yaml2Path = $argv[2];

$yaml1 = file_get_contents($yaml1Path);
$yaml2 = file_get_contents($yaml2Path);

$arr1 = yaml_parse($yaml1);
$arr2 = yaml_parse($yaml2);

$finalArr = array_replace_recursive($arr2, $arr1);
echo yaml_emit($finalArr, YAML_UTF8_ENCODING);