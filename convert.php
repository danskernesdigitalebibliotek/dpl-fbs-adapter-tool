<?php

use Symfony\Component\Yaml\Yaml;

require __DIR__.'/vendor/autoload.php';

$yaml = Yaml::parseFile($argv[1]);

$filterPaths = [
    // Patron
    '/external/{agencyid}/patrons/{patronid}/v2',
    '/external/{agencyid}/patrons/{patronid}/v5',
    // Patron create
    '/external/{agencyid}/patrons/v4',
    '/external/{agencyid}/patrons/withGuardian/v1',
    // Loans
    '/external/{agencyid}/patrons/{patronid}/loans/v2',
    '/external/{agencyid}/patrons/{patronid}/loans/renew/v2',
    // Reservations
    '/external/v1/{agencyid}/patrons/{patronid}/reservations/v2',
    '/external/v1/{agencyid}/patrons/{patronid}/reservations',
    // Fees
    '/external/{agencyid}/patron/{patronid}/fees/v2',
    // Branches etc.
    '/external/v1/{agencyid}/branches',
    '/external/{agencyid}/catalog/availability/v3',
    '/external/{agencyid}/catalog/holdings/v3',
];
$output = [
    'swagger' => $yaml['swagger'],
    'info' => [
        'title' => 'FBS Adapter',
        'version' => $yaml['info']['version']
    ],
    'basePath' => '/'
];

foreach ($yaml['paths'] as $path => $data) {
    if (in_array($path, $filterPaths)) {
        echo $path,"\n";
    }
}


