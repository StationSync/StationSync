<?php
// Funktion zur umwandlung von xml zu JSON
function xml_to_json($xml, $options = false) {
    // Überprüfen ob die Eingabe eine Datei oder ein XML String ist
    if (file_exists($xml)) {
        $xml = simplexml_load_file($xml);
    } else {
        $xml = simplexml_load_string($xml);
    }

    //Überprüfen ob XML erfolgreich geladen wurde
    if ($xml === false) {
        return false;
    }

    //Konvertiert ein einfaches XML Element in ein Array 
    $array = xml_to_array($xml);

    //Konvertiert ein array zu JSON 
    $json = json_encode($array, $options);

    return $json;
}

function xml_to_array($xml) {
    // Wenn es ein einfaches XML Element ist, wird es in ein array umgewandelt
    if ($xml instanceof SimpleXMLElement) {
        $xml = (array) $xml;
    }

    //Wenn es ein Array ist, werden alle Elemente verarbeitet
    if (is_array($xml)) {
        $result = array();
        foreach ($xml as $key => $value) {
            // Handle attributes
            if ($key === '@attributes') {
                $result['attributes'] = $value;
                continue;
            }

            //Rekursives umwandeln der "unter" Elemente (child elements) 
            if (is_object($value) || is_array($value)) {
                $result[$key] = xml_to_array($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    // Rüchgabe wenn der wert kein Objekt oder Array ist
    return $xml;
}
