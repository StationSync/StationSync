<?php

$station =  $_GET['trainStation'];
$evaNumber = $_GET['evaNumber'];
$date = $_GET['datum'];
$time = $_GET['uhrzeit'];
$number = $_GET['number'];

$formattedDate = date('ymd', strtotime($date));
$hour = date('H', strtotime($time));

require_once '../api_routes/timetable.php';
require_once '../api_routes/fasta_stations.php';

// Initialize connections array
$connections = [];
$errorMessage = '';
$debugInfo = '';

$timetable = getTimetableByStation($evaNumber, $formattedDate, $hour);
$facilities = getFacilitiesByStation($number);
$changes = getChangesByStation($evaNumber);

// If $facilities is a JSON string, decode it
if (is_string($facilities)) {
    $facilities = json_decode($facilities, true);
}

if ($timetable) {

    // Check if timetable is empty or false
    if ($timetable === false || $timetable === null) {
        $errorMessage = "getTimetableByStation() returned false or null.";
    } elseif (trim($timetable) === '') {
        $errorMessage = "Timetable data is an empty string.";
    } else {
        // Attempt to parse the XML
        libxml_use_internal_errors(true); // Enable user error handling
        $xml = simplexml_load_string($timetable);

        // Check for XML parsing errors
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMessage = "XML Parsing Errors:\n";
            foreach ($errors as $error) {
                $errorMessage .= "  - " . $error->message . "\n";
            }
            libxml_clear_errors();
        } else {
            // Successfully parsed XML
            // Loop through each service (s) in the XML
            foreach ($xml->s as $service) {
                $connection = [
                    'id' => (string)$service['id'],
                    'train_line' => [
                        'type' => (string)$service->tl['c'],
                        'number' => (string)$service->tl['n'],
                        'operator' => (string)$service->tl['o']
                    ]
                ];

                // Check for arrival
                if ($service->ar) {
                    $connection['arrival'] = [
                        'platform' => (string)$service->ar['pp'],
                        'time' => (string)$service->ar['pt'],
                        'line' => (string)$service->ar['l'],
                        'route_before' => explode('|', (string)$service->ar['ppth'])
                    ];
                }

                // Check for departure
                if ($service->dp) {
                    $connection['departure'] = [
                        'platform' => (string)$service->dp['pp'],
                        'time' => (string)$service->dp['pt'],
                        'line' => (string)$service->dp['l'],
                        'route_after' => explode('|', (string)$service->dp['ppth'])
                    ];
                }

                $connections[] = $connection;
            }

            // Check if no connections were found
            if (empty($connections)) {
                $errorMessage = "No connections found for this station and time.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Zugverbindungen von <?php echo htmlspecialchars($station); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* ... previous styles ... */
        .debug {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-train me-2"></i>StationSync</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand">Zugverbindungen von <?php echo htmlspecialchars($station); ?></h1>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Startseite</a></li>
                    <li class="nav-item"><a class="nav-link" href="./about.html">Über uns</a></li>
                </ul>
            </div>
    </nav>

    <?php if (!empty($errorMessage)): ?>
        <div class="error">
            <?php echo nl2br(htmlspecialchars($errorMessage)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($debugInfo)): ?>
        <div class="debug">
            <?php echo nl2br(htmlspecialchars($debugInfo)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($connections)): ?>
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Zug</th>
                    <th>Gleis Ankunft</th>
                    <th>Zeit Ankunft</th>
                    <th>Route vor der Ankunft in <?php echo htmlspecialchars($station); ?></th>
                    <th>Gleis Abfahrt</th>
                    <th>Zeit Anfahrt</th>
                    <th>Route nach der Abfahrt in <?php echo htmlspecialchars($station); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($connections as $connection): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($connection['train_line']['type'] . ' ' .
                                $connection['train_line']['number'] . ' (' .
                                $connection['train_line']['operator'] . ')'); ?>
                        </td>

                        <!-- Arrival Information -->
                        <td>
                            <?php echo isset($connection['arrival']['platform']) ?
                                htmlspecialchars($connection['arrival']['platform']) : ''; ?>
                        </td>
                        <td>
                            <?php echo isset($connection['arrival']['time']) ?
                                htmlspecialchars(date('H:i', strtotime($connection['arrival']['time']))) : ''; ?>
                        </td>
                        <td>
                            <?php if (isset($connection['arrival']['route_before'])): ?>
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(implode(' → ', $connection['arrival']['route_before'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Departure Information -->
                        <td>
                            <?php echo isset($connection['departure']['platform']) ?
                                htmlspecialchars($connection['departure']['platform']) : ''; ?>
                        </td>
                        <td>
                            <?php echo isset($connection['departure']['time']) ?
                                htmlspecialchars(date('H:i', strtotime($connection['departure']['time']))) : ''; ?>
                        </td>
                        <td>
                            <?php if (isset($connection['departure']['route_after'])): ?>
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(implode(' → ', $connection['departure']['route_after'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No connection information available.</p>
    <?php endif; ?>

    <?php if (!empty($facilities)): ?>
        <div class="container mt-4">
            <h2>Einrichtungen im Bahnhof</h2>
            <table class="table table-striped table-hover table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Typ</th>
                        <th>Beschreibung</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facilities['facilities'] as $facility): ?>
                        <tr>
                            <td>
                                <?php
                                // Translate facility type if needed
                                $facilityType = match ($facility['type']) {
                                    'ELEVATOR' => 'Aufzug',
                                    'ESCALATOR' => 'Rolltreppe',
                                    default => htmlspecialchars($facility['type'])
                                };
                                echo $facilityType;
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($facility['description']); ?></td>
                            <td>
                                <?php
                                // Color code the state
                                $stateClass = match ($facility['state']) {
                                    'ACTIVE' => 'text-success',
                                    'INACTIVE' => 'text-danger',
                                    default => 'text-warning'
                                };
                                ?>
                                <span class="<?php echo $stateClass; ?>">
                                    <?php
                                    // Translate state if needed
                                    $stateText = match ($facility['state']) {
                                        'ACTIVE' => 'Verfügbar',
                                        'INACTIVE' => 'Nicht verfügbar',
                                        'UNKNOWN' => 'Unbekannt',
                                        default => htmlspecialchars($facility['state'])
                                    };
                                    echo $stateText;
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>