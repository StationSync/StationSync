<?php
// Standart für form Eingaben
$trainStation = isset($_GET['trainStation']) ? htmlspecialchars($_GET['trainStation']) : '';
$trainStation2 = isset($_GET['trainStation2']) ? htmlspecialchars($_GET['trainStation']) : '';
$datum = isset($_GET['datum']) ? htmlspecialchars($_GET['datum']) : date('Y-m-d');
$uhrzeit = isset($_GET['uhrzeit']) ? htmlspecialchars($_GET['uhrzeit']) : date('H:i');

// Import Code zum laden aller Stationen
require_once './api_routes/stada_stations.php';

// Cache Path
$cacheFilePath = __DIR__ . '\cache\stations_cache.json';

// Check ob cache bereits vorhanden ist, wenn nicht erstellen
if (file_exists($cacheFilePath) && filesize($cacheFilePath) > 0) {
    // Cache Daten lesen
    $stations = file_get_contents($cacheFilePath);
} else {
    // Stationen laden
    $stations = getStationsByCity();

    // Cache updaten
    file_put_contents($cacheFilePath, $stations);
}
$stationsArray = json_decode($stations, true);
$stationData = []; // StationsDaten Array

// Loop auf alle Stationen 
foreach ($stationsArray['result'] as $station) {
    // Überprüfen ob die EVA Nummer existiert und nicht leer ist 
    if (!empty($station['evaNumbers'])) {
        // Holen der erste Eva Nummer (Ausgehend davon das die erste auch die Haupt Nummer ist)
        $evaNumber = $station['evaNumbers'][0]['number'];

        // Hinzufügen eines checks für die Geografischen Koordinaten
        $coords = null;
        $hasSteplessAccess = null;

        if (isset($station['ril100Identifiers'][0]['geographicCoordinates']['coordinates'])) {
            $coords = $station['ril100Identifiers'][0]['geographicCoordinates']['coordinates'];
        }

        if (isset($station['hasSteplessAccess'])) {
            $hasSteplessAccess = $station['hasSteplessAccess'];
        }

        $stationData[] = [
            'name' => $station['name'],
            'evaNumber' => $evaNumber,
            'number' => $station['number'],
            'zipcode' => $station['mailingAddress']['zipcode'],
            'city' => $station['mailingAddress']['city'],
            'street' => $station['mailingAddress']['street'],
            'coords' => $coords,
            'hasParking' => $station['hasParking'],
            'hasBicycleParking' => $station['hasBicycleParking'],
            'hasLocalPublicTransport' => $station['hasLocalPublicTransport'],
            'hasPublicFacilities' => $station['hasPublicFacilities'],
            'hasLockerSystem' => $station['hasLockerSystem'],
            'hasTaxiRank' => $station['hasTaxiRank'],
            'hasTravelNecessities' => $station['hasTravelNecessities'],
            'hasSteplessAccess' => $hasSteplessAccess,
            'hasWiFi' => $station['hasWiFi'],
            'hasTravelCenter' => $station['hasTravelCenter'],
            'hasRailwayMission' => $station['hasRailwayMission'],
            'hasDBLounge' => $station['hasDBLounge'],
            'hasLostAndFound' => $station['hasLostAndFound'],
            'hasCarRental' => $station['hasCarRental']
        ];
    }
}
?>

<!-- Festlegen des Titel und der zugehörigen Style.css sowie Links zu den Libary´s -->
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StationSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css_files/style.css">
</head>

<!-- Die Navigationbar -->

<body class="bg-light d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand"><i class="fas fa-train me-2"></i>StationSync</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item" disabled><a class="nav-link" href="index.php">Startseite</a></li>
                    </ul>
                </div>
        </nav>

        <!-- Das Fenster indem die Daten angegeben werden können -->
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h2 class="card-title text-center mb-4">Finden Sie Ihre Bahnverbindung</h2>
                            <form action="pages/connections.php" method="GET">
                                <div class="mb-3">
                                    <label for="trainStation" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Start-Bahnhof</label>
                                    <input type="text" id="trainStation" name="trainStation"
                                        class="form-control"
                                        value="<?php echo $trainStation; ?>"
                                        required>
                                </div>
                                <input type="hidden" id="evaNumber" name="evaNumber" value="">
                                <input type="hidden" id="number" name="number" value="">
                                <input type="hidden" id="zipcode" name="zipcode" value="">
                                <input type="hidden" id="city" name="city" value="">
                                <input type="hidden" id="street" name="street" value="">
                                <input type="hidden" id="coords" name="coords" value="">
                                <input type="hidden" id="hasParking" name="hasParking" value="">
                                <input type="hidden" id="hasBicycleParking" name="hasBicycleParking" value="">
                                <input type="hidden" id="hasLocalPublicTransport" name="hasLocalPublicTransport" value="">
                                <input type="hidden" id="hasPublicFacilities" name="hasPublicFacilities" value="">
                                <input type="hidden" id="hasLockerSystem" name="hasLockerSystem" value="">
                                <input type="hidden" id="hasTaxiRank" name="hasTaxiRank" value="">
                                <input type="hidden" id="hasTravelNecessities" name="hasTravelNecessities" value="">
                                <input type="hidden" id="hasSteplessAccess" name="hasSteplessAccess" value="">
                                <input type="hidden" id="hasWiFi" name="hasWiFi" value="">
                                <input type="hidden" id="hasTravelCenter" name="hasTravelCenter" value="">
                                <input type="hidden" id="hasRailwayMission" name="hasRailwayMission" value="">
                                <input type="hidden" id="hasDBLounge" name="hasDBLounge" value="">
                                <input type="hidden" id="hasLostAndFound" name="hasLostAndFound" value="">
                                <input type="hidden" id="hasCarRental" name="hasCarRental" value="">
                                <div class="mb-3">
                                    <label for="trainStation2" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Ziel-Bahnhof (optional)</label>
                                    <input type="text" id="trainStation2" name="trainStation2"
                                        class="form-control"
                                        value="<?php echo $trainStation2; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="datum" class="form-label"><i class="far fa-calendar-alt me-2"></i>Reisedatum</label>
                                    <input type="date"
                                        id="datum"
                                        name="datum"
                                        class="form-control"
                                        value="<?php echo $datum; ?>"
                                        min="<?php echo date('Y-m-d'); ?>"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="uhrzeit" class="form-label"><i class="far fa-clock me-2"></i>Reisezeit</label>
                                    <input type="time" id="uhrzeit" name="uhrzeit"
                                        class="form-control"
                                        value="<?php echo $uhrzeit; ?>"
                                        required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-search me-2"></i>Bahnverbindung suchen</button>
                            </form>
                            <div id="loadingSpinner" class="text-center d-none">
                                <div class="spinner-border text-primary" style="width: 1rem; height: 1rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Der Footer -->
    <?php include './components/footer.php'; ?>

    <script src="components/autocomplete.js"></script>
    <script>
        // Stationen automatische Vervollständigung
        const stations = <?php echo json_encode(array_column($stationData, 'name')); ?>;
        const stationData = <?php echo json_encode($stationData); ?>;

        // Initalisieren der automatische Vervollständigung 
        const input = document.getElementById('trainStation');
        const evaInput = document.getElementById('evaNumber'); 
        const numberInput = document.getElementById('number'); 
        const zipcodeInput = document.getElementById('zipcode'); 
        const cityInput = document.getElementById('city'); 
        const streetInput = document.getElementById('street'); 
        const coordsInput = document.getElementById('coords'); 
        const hasParkingInput = document.getElementById('hasParking');
        const hasBicycleParkingInput = document.getElementById('hasBicycleParking');
        const hasLocalPublicTransportInput = document.getElementById('hasLocalPublicTransport');
        const hasPublicFacilitiesInput = document.getElementById('hasPublicFacilities');
        const hasLockerSystemInput = document.getElementById('hasLockerSystem');
        const hasTaxiRankInput = document.getElementById('hasTaxiRank');
        const hasTravelNecessitiesInput = document.getElementById('hasTravelNecessities');
        const hasSteplessAccessInput = document.getElementById('hasSteplessAccess');
        const hasWiFiInput = document.getElementById('hasWiFi');
        const hasTravelCenterInput = document.getElementById('hasTravelCenter');
        const hasRailwayMissionInput = document.getElementById('hasRailwayMission');
        const hasDBLoungeInput = document.getElementById('hasDBLounge');
        const hasLostAndFoundInput = document.getElementById('hasLostAndFound');
        const hasCarRentalInput = document.getElementById('hasCarRental');

        const autocomplete = new AutocompleteInput(input, {
            data: stations,
            placeholder: 'Nach Bahnhof suchen...',
            onSelect: (selectedItem) => {
                // Finden der Korespondierenden EVA Nummer
                const selectedStation = stationData.find(station => station.name === selectedItem);
                if (selectedStation) {
                    console.log('Selected Station:', selectedStation.name);
                    console.log('EVA Number:', selectedStation.evaNumber);
                    console.log('Number:', selectedStation.number);
                    evaInput.value = selectedStation.evaNumber; 
                    numberInput.value = selectedStation.number; 
                    zipcodeInput.value = selectedStation.zipcode; 
                    cityInput.value = selectedStation.city; 
                    streetInput.value = selectedStation.street; 
                    coordsInput.value = selectedStation.coords; 
                    hasParkingInput.value = selectedStation.hasParking;
                    hasBicycleParkingInput.value = selectedStation.hasBicycleParking;
                    hasLocalPublicTransportInput.value = selectedStation.hasLocalPublicTransport;
                    hasPublicFacilitiesInput.value = selectedStation.hasPublicFacilities;
                    hasLockerSystemInput.value = selectedStation.hasLockerSystem;
                    hasTaxiRankInput.value = selectedStation.hasTaxiRank;
                    hasTravelNecessitiesInput.value = selectedStation.hasTravelNecessities;
                    hasSteplessAccessInput.value = selectedStation.hasSteplessAccess;
                    hasWiFiInput.value = selectedStation.hasWiFi;
                    hasTravelCenterInput.value = selectedStation.hasTravelCenter;
                    hasRailwayMissionInput.value = selectedStation.hasRailwayMission;
                    hasDBLoungeInput.value = selectedStation.hasDBLounge;
                    hasLostAndFoundInput.value = selectedStation.hasLostAndFound;
                    hasCarRentalInput.value = selectedStation.hasCarRental;
                }
            }
        });

        const input2 = document.getElementById('trainStation2');
        const autocomplete2 = new AutocompleteInput(input2, {
            data: stations,
            placeholder: 'Nach Bahnhof suchen...',
            onSelect: (selectedItem) => {
                // Finden der Korespondierenden EVA Nummer
                const selectedStation = stationData.find(station => station.name === selectedItem);
                if (selectedStation) {
                    console.log('Selected Station:', selectedStation.name);
                }
            }
        });
    </script>

    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            // Erhalten der Submit Schaltfläche und den Lade-Spinner 
            const submitButton = document.querySelector('button[type="submit"]');
            const loadingSpinner = document.getElementById('loadingSpinner');

            // Ausschalten der Schaltfläche und Zeigen des Spinners
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-xs" role="status" aria-hidden="true"></span> Suchen...';
        });
    </script>

    <!-- Bootstrap JS und jQuery importieren -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
