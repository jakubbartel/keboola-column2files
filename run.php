<?php

require __DIR__ . '/vendor/autoload.php';

use Keboola\ColumnToFilesProcessor\Exception\UserException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

try {
    $component = new \Keboola\ColumnToFilesProcessor\Component();
    $component->run();
} catch(UserException | InvalidConfigurationException $e) {
    error_log($e->getMessage());

    exit(1);
} catch(Throwable $e) {
    error_log(get_class($e) . ': ' . $e->getMessage());
    error_log('File: ' . $e->getFile());
    error_log('Line: ' . $e->getLine());
    error_log('Code: ' . $e->getCode());
    error_log('Trace: ' . $e->getTraceAsString());

    exit(2);
}

exit(0);
