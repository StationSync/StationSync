<?php
// Set default values for form inputs
$trainStation = isset($_GET['trainStation']) ? htmlspecialchars($_GET['trainStation']) : '';
$datum = isset($_GET['datum']) ? htmlspecialchars($_GET['datum']) : date('Y-m-d');
$uhrzeit = isset($_GET['uhrzeit']) ? htmlspecialchars($_GET['uhrzeit']) : date('H:i');

// Fetch station data
require_once './api_routes/stada_stations.php';
$stations = getStationsByCity();

if ($stations) {

    // Assuming $stations is an array of station data as provided in your context
    $stationsArray = json_decode($stations, true); // Decode the JSON string into an associative array
    $stationNames = []; // Initialize an empty array to hold the station names

    // Loop through each station and extract the "name"
    foreach ($stationsArray['result'] as $station) {
        $stationNames[] = $station['name']; // Add the name to the stationNames array
    }

    // Proceed to render the HTML only if stations are retrieved successfully
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>StationSync</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="css_files/style.css">
    </head>
    <body>
        <header class="bg-primary text-white p-3">
            <h1 class="text-center">StationSync</h1>
            <nav>
                <ul class="nav d-flex justify-content-center">
                    <li class="nav-item"><a class="nav-link text-white" href="index.php">Startseite</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="pages/about.php">Über uns</a></li>
                </ul>
            </nav>
        </header>

        <div class="container mt-4">
            <form action="pages/connections.php" method="GET">
                <div class="input-container mb-3 text-center">
                    <label for="trainStation">Bahnhof:</label>
                    <input type="text" id="trainStation" name="trainStation" 
                           class="form-control" 
                           value="<?php echo $trainStation; ?>" 
                           required>
                </div>
                <div class="input-container mb-3 text-center">
                    <label for="datum">Reisedatum:</label>
                    <input type="date" id="datum" name="datum" 
                           class="form-control" 
                           value="<?php echo $datum; ?>" 
                           required>
                </div>
                <div class="input-container mb-3 text-center">
                    <label for="uhrzeit">Reisezeit:</label>
                    <input type="time" id="uhrzeit" name="uhrzeit" 
                           class="form-control" 
                           value="<?php echo $uhrzeit; ?>" 
                           required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Bahnverbindung suchen</button>
            </form>
        </div>
        
        <?php //include 'includes/footer.php'; ?>
        <?php //include 'includes/modals.php'; ?>

        <!-- Import der autocomplete Komponente -->
        <script src="components/autocomplete.js"></script>

        <script>
            // Train stations autocomplete
            const stations = <?php echo json_encode($stationNames); ?>;

            // Initialize the autocomplete
            const input = document.getElementById('trainStation');
            const autocomplete = new AutocompleteInput(input, {
                data: stations,
                placeholder: 'Nach Bahnhof suchen...',
                onSelect: (selectedItem) => {
                    console.log('Selected:', selectedItem);
                }
            });
        </script>

        <!-- Bootstrap JS und jQuery importieren -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
    <?php
} else {
    // Handle the case where no stations were found
    echo "<p>Keine Bahnhöfe gefunden. Bitte versuchen Sie es später erneut.</p>";
}
?>