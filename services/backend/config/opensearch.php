<?php

declare(strict_types=1);

return [
    'host' => env('OPENSEARCH_HOST', 'http://opensearch:9200'),
    'username' => env('OPENSEARCH_USERNAME'),
    'password' => env('OPENSEARCH_PASSWORD'),
    // Only meaningful over https — the k8s deployment's OpenSearch security plugin
    // generates a self-signed demo cert (no external CA for cluster-internal traffic),
    // so verification is disabled there. Defaults true so a misconfigured https host
    // fails closed rather than silently skipping verification.
    'ssl_verification' => env('OPENSEARCH_SSL_VERIFY', true),
];
