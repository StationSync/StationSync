<?php
// aufrufen der Stations Daten API mithilfe von PHP, zurück kommt eine JSON Objekt
require __DIR__ . '/../helper_functions/getenv.php';

function getStationsByCity() {
    loadEnv();

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://apis.deutschebahn.com/db-api-marketplace/apis/station-data/v2/stations",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "DB-Api-Key: " . getenv('CLIENT_SECRET'),
            "DB-Client-ID: " . getenv('CLIENT_ID'),
            "accept: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return $response;
    }
}
