<?php

function console_log($data, $context = 'log') {
    // Validate context
    $validContexts = ['log', 'info', 'warn', 'error', 'debug'];
    $context = in_array($context, $validContexts) ? $context : 'log';

    // Convert data to JSON
    if (is_null($data)) {
        $output = 'null';
    } elseif (is_bool($data)) {
        $output = $data ? 'true' : 'false';
    } elseif (is_scalar($data)) {
        $output = json_encode($data);
    } else {
        $output = json_encode($data, JSON_PRETTY_PRINT);
    }

    // Escape single quotes and prepare the script
    $output = str_replace("'", "\\'", $output);

    // Output script to log to console
    echo "<script>console.{$context}('{$output}');</script>";
}