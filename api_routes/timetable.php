<?php

require __DIR__ . '/../helper_functions/getenv.php';

function getTimetableByStation($evaNumber, $date, $hour) {
    loadEnv();

    $curl = curl_init();
    
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://apis.deutschebahn.com/db-api-marketplace/apis/timetables/v1/plan/" . $evaNumber . "/" . $date . "/" . $hour,
      //CURLOPT_URL => "https://apis.deutschebahn.com/db-api-marketplace/apis/timetables/v1/plan/8010009/241202/12",
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
      echo "cURL Error #:" . $err;
      return null;
    } else {
      return $response;
    }
}

function getChangesByStation($evaNumber) {
  loadEnv();

  $curl = curl_init();
  
  curl_setopt_array($curl, [
    CURLOPT_URL => "https://apis.deutschebahn.com/db-api-marketplace/apis/timetables/v1/fchg/" . $evaNumber,
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