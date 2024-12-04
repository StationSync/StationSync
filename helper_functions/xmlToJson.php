<?php

function xml_to_json($xml, $options = false) {
    // Check if the input is a file path or XML string
    if (file_exists($xml)) {
        $xml = simplexml_load_file($xml);
    } else {
        $xml = simplexml_load_string($xml);
    }

    // Check if XML loading was successful
    if ($xml === false) {
        return false;
    }

    // Convert SimpleXMLElement to array
    $array = xml_to_array($xml);

    // Convert array to JSON
    $json = json_encode($array, $options);

    return $json;
}

function xml_to_array($xml) {
    // If it's a SimpleXMLElement, convert to array
    if ($xml instanceof SimpleXMLElement) {
        $xml = (array) $xml;
    }

    // If it's an array, process its elements
    if (is_array($xml)) {
        $result = array();
        foreach ($xml as $key => $value) {
            // Handle attributes
            if ($key === '@attributes') {
                $result['attributes'] = $value;
                continue;
            }

            // Recursively convert child elements
            if (is_object($value) || is_array($value)) {
                $result[$key] = xml_to_array($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    // Return the value if it's not an object or array
    return $xml;
}

/*

// Example usage
try {
    // Convert XML from a file
    $json1 = xml_to_json('example.xml');
    echo "JSON from file:\n" . $json1 . "\n\n";

    // Convert XML from a string
    $xmlString = '<?xml version="1.0" encoding="UTF-8"?>
    <root>
        <person>
            <name>John Doe</name>
            <age>30</age>
            <city>New York</city>
        </person>
    </root>';
    
    $json2 = xml_to_json($xmlString, JSON_PRETTY_PRINT);
    echo "JSON from string:\n" . $json2 . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

*/