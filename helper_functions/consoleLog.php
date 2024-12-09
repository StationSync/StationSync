<?php

function console_log($data, $context = 'log') {
    // Validiert den Kontext
    $validContexts = ['log', 'info', 'warn', 'error', 'debug'];
    $context = in_array($context, $validContexts) ? $context : 'log';

    //Konvertiert die Daten in JSON
    if (is_null($data)) {
        $output = 'null';
    } elseif (is_bool($data)) {
        $output = $data ? 'true' : 'false';
    } elseif (is_scalar($data)) {
        $output = json_encode($data);
    } else {
        $output = json_encode($data, JSON_PRETTY_PRINT);
    }

    //Entfernen des einfachen hochkommas und vorbereiten für das Script
    $output = str_replace("'", "\\'", $output);

    //Ausgabe des Output Skriptes zu der log Konsole 
    echo "<script>console.{$context}('{$output}');</script>";
}
