<?php
// Set default values for form inputs
$trainStation = isset($_GET['trainStation']) ? htmlspecialchars($_GET['trainStation']) : '';
$datum = isset($_GET['datum']) ? htmlspecialchars($_GET['datum']) : date('Y-m-d');
$uhrzeit = isset($_GET['uhrzeit']) ? htmlspecialchars($_GET['uhrzeit']) : date('H:i');

// Fetch station data
require_once './api_routes/stada_stations.php';
$stations = getStationsByCity();

if ($stations) {
    $stationsArray = json_decode($stations, true);
    $stationNames = array_column($stationsArray['result'], 'name');
?>
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
                    <li class="nav-item"><a class="nav-link" href="pages/about.php">Über uns</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Finden Sie Ihre Bahnverbindung</h2>
                        <form action="pages/connections.php" method="GET">
                            <div class="mb-3">
                                <label for="trainStation" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Bahnhof</label>
                                <input type="text" id="trainStation" name="trainStation" 
                                       class="form-control" 
                                       value="<?php echo $trainStation; ?>" 
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="datum" class="form-label"><i class="far fa-calendar-alt me-2"></i>Reisedatum</label>
                                <input type="date" id="datum" name="datum" 
                                       class="form-control" 
                                       value="<?php echo $datum; ?>" 
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="components/autocomplete.js"></script>
    <script>
        const stations = <?php echo json_encode($stationNames); ?>;
        const input = document.getElementById('trainStation');
        const autocomplete = new AutocompleteInput(input, {
            data: stations,
            placeholder: 'Nach Bahnhof suchen...',
            onSelect: (selectedItem) => {
                console.log('Selected:', selectedItem);
            }
        });
    </script>
</body>
</html>
<?php
} else {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Keine Bahnhöfe gefunden. Bitte versuchen Sie es später erneut.</div></div>";
}
?>