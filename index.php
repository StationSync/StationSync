<?php
// Set default values for form inputs
$trainStation = isset($_GET['trainStation']) ? htmlspecialchars($_GET['trainStation']) : '';
$trainStation2 = isset($_GET['trainStation2']) ? htmlspecialchars($_GET['trainStation']) : '';
$datum = isset($_GET['datum']) ? htmlspecialchars($_GET['datum']) : date('Y-m-d');
$uhrzeit = isset($_GET['uhrzeit']) ? htmlspecialchars($_GET['uhrzeit']) : date('H:i');

// Fetch station data (all stations in Germany)
require_once './api_routes/stada_stations.php';

$cacheFilePath = __DIR__ . '\cache\stations_cache.json';

// Check if the cache file exists and is not empty
if (file_exists($cacheFilePath) && filesize($cacheFilePath) > 0) {
    // Read the cached data
    $stations = file_get_contents($cacheFilePath);
} else {
    // Fetch station data (all stations in Germany)
    $stations = getStationsByCity();

    // Update the cache file with the new data
    file_put_contents($cacheFilePath, $stations);
}
$stationsArray = json_decode($stations, true);
$stationData = []; // Initialize an array to hold station names and EVA numbers

// Loop through each station and extract the name and EVA number
foreach ($stationsArray['result'] as $station) {
    // Check if evaNumbers exist and is not empty
    if (!empty($station['evaNumbers'])) {
        // Get the first EVA number (assuming the first one is the main number)
        $evaNumber = $station['evaNumbers'][0]['number'];

        $stationData[] = [
            'name' => $station['name'],
            'evaNumber' => $evaNumber,
            'number' => $station['number']
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

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-train me-2"></i>StationSync</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Startseite</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/about.html">Über uns</a></li>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Der Footer -->

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 StationSync. Alle Rechte vorbehalten.</p>
            <div>
                <button type="button" class="btn btn-link text-light" data-bs-toggle="modal" data-bs-target="#haftungsausschluss-modal">Haftungsausschluss</button>
                <button type="button" class="btn btn-link text-light" data-bs-toggle="modal" data-bs-target="#datenschutz-modal">Datenschutz</button>
                <button type="button" class="btn btn-link text-light" data-bs-toggle="modal" data-bs-target="#kontakt-modal">Kontakt</button>
            </div>
        </div>
    </footer>

    <!-- Festlegen der Daten die Bei den Buttons vom Footer geöffnet werden (erst Kontakt dann Haftungsausschluss und zuletzt Datenschutz) -->
    <div class="modal fade" id="kontakt-modal" tabindex="-1" role="dialog" aria-labelledby="kontakt-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kontakt-modal-label">Kontakt</h5>
                </div>
                <div class="modal-body">
                    <p>
                        Für Fragen oder Anliegen wenden Sie sich bitte an unseren
                        <a href="pages/contact.html">Kontakt</a>.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="haftungsausschluss-modal" tabindex="-1" role="dialog"
        aria-labelledby="haftungsausschluss-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="haftungsausschluss-modal-label">Haftungsausschluss</h5>
                </div>
                <div class="modal-body">
                    <p>
                        Die auf dieser Website bereitgestellten Informationen und Dienstleistungen werden ohne
                        Gewährleistung für Richtigkeit, Vollständigkeit oder Aktualität bereitgestellt.
                        Wir übernehmen keine Haftung für Verzögerungen oder Ausfälle von Bahnverbindungen, die aufgrund
                        von Umständen außerhalb unserer Kontrolle entstehen.
                        Bitte beachten Sie, dass die Ankunfts- und Abfahrtszeiten von Zügen je nach Verkehrslage
                        variieren können.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="datenschutz-modal" tabindex="-1" role="dialog" aria-labelledby="datenschutz-modal-label"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="datenschutz-modal-label">Datenschutz</h5>
                </div>
                <div class="modal-body">
                    <p>
                        Wir nehmen den Schutz Ihrer persönlichen Daten ernst. Bitte lesen Sie unsere
                        <a href="pages/datenschutz.html">Datenschutzerklärung</a>, um mehr über die Verarbeitung und den
                        Schutz Ihrer Daten zu erfahren.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="components/autocomplete.js"></script>
    <script>
        // Train stations autocomplete
        const stations = <?php echo json_encode(array_column($stationData, 'name')); ?>;
        const stationData = <?php echo json_encode($stationData); ?>;

        // Initialize the autocomplete
        const input = document.getElementById('trainStation');
        const evaInput = document.getElementById('evaNumber'); // Get the EVA number input
        const numberInput = document.getElementById('number'); // Get the EVA number input
        const autocomplete = new AutocompleteInput(input, {
            data: stations,
            placeholder: 'Nach Bahnhof suchen...',
            onSelect: (selectedItem) => {
                // Find the corresponding EVA number
                const selectedStation = stationData.find(station => station.name === selectedItem);
                if (selectedStation) {
                    console.log('Selected Station:', selectedStation.name);
                    console.log('EVA Number:', selectedStation.evaNumber);
                    console.log('Number:', selectedStation.number);
                    evaInput.value = selectedStation.evaNumber; // Set the EVA number in the hidden input
                    numberInput.value = selectedStation.number; // Set the EVA number in the hidden input
                }
            }
        });

        const input2 = document.getElementById('trainStation2');
        const autocomplete2 = new AutocompleteInput(input2, {
            data: stations,
            placeholder: 'Nach Bahnhof suchen...',
            onSelect: (selectedItem) => {
                // Find the corresponding EVA number
                const selectedStation = stationData.find(station => station.name === selectedItem);
                if (selectedStation) {
                    console.log('Selected Station:', selectedStation.name);
                }
            }
        });
    </script>

    <script>
        // Train stations autocomplete
        const stations = <?php echo json_encode(array_column($stationData, 'name')); ?>;
        const stationData = <?php echo json_encode($stationData); ?>;

        // Initialize the autocomplete
    </script>

    <!-- Bootstrap JS und jQuery importieren -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>