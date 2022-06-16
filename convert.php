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
    'basePath' => '/',
    'paths' => [],
];

$tagsFilter = [];
$refFilter = [];
foreach ($yaml['paths'] as $path => $data) {
    if (in_array($path, $filterPaths)) {
        // Change '{agencyid}' path parameter to 'agencyid' string.
        $path = str_replace('{agencyid}', 'agencyid', $path);

        // Change '{patronid}' path parameter to 'patronid' string.
        $path = str_replace('{patronid}', 'patronid', $path);

        foreach ($data as $name => $op) {
            // Find all used tags, so they can be set in output.
            $tagsFilter[] = $op['tags'][0];
            foreach ($op['parameters'] as $index => $parameter) {
                // Remove '{agencyid}' parameters.
                if ('agencyid' === $parameter['name']) {
                    unset($data[$name]['parameters'][$index]);
                }

                // Remove '{patronid}' parameters.
                if ('patronid' === $parameter['name']) {
                    unset($data[$name]['parameters'][$index]);
                }

                if (isset($parameter['schema']['$ref'])) {
                    $parts = explode('/', $parameter['schema']['$ref']);
                    $refFilter[] = end($parts);
                }
            }

            // Reset index numbers.
            $data[$name]['parameters'] = array_values($data[$name]['parameters']);
            if (empty($data[$name]['parameters'])) {
                unset($data[$name]['parameters']);
            }

            // Find all used `$ref`, so they can be set in output.
            foreach ($op['responses'] as $respons) {
                if (isset($respons['schema'])) {
                    if (isset($respons['schema']['$ref'])) {
                        $parts = explode('/', $respons['schema']['$ref']);
                        $refFilter[] = end($parts);
                    } elseif (isset($respons['schema']['items']['$ref'])) {
                        $parts = explode('/', $respons['schema']['items']['$ref']);
                        $refFilter[] = end($parts);
                    }
                }
            }
        }

        $output['paths'][$path] = $data;
    }
}

// Find definitions that $ref other definitions.
array_walk($yaml['definitions'], function ($definition) use (&$refFilter) {
    foreach ($definition['properties'] as $property) {
        if (isset($property['$ref'])) {
            $parts = explode('/', $property['$ref']);
            $refFilter[] = end($parts);
        }
        if (isset($property['items']['$ref'])) {
            $parts = explode('/', $property['items']['$ref']);
            $refFilter[] = end($parts);
        }
    }
});

// Only output tags used by the paths.
$tagsFilter = array_unique($tagsFilter);
$tags = array_filter($yaml['tags'], function ($tag) use ($tagsFilter) {
    return in_array($tag['name'], $tagsFilter);
});
$output['tags'] = array_values($tags);

// Only output definitions used via $refs.
$refFilter = array_unique($refFilter);
$output['definitions'] = array_filter($yaml['definitions'], function ($name) use ($refFilter) {
    return in_array($name, $refFilter);
}, ARRAY_FILTER_USE_KEY);

$yaml = Yaml::dump($output, 12);
file_put_contents($argv[2], $yaml);
