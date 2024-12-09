<?php

$station =  $_GET['trainStation'];
$station2 =  $_GET['trainStation2'];
$evaNumber = $_GET['evaNumber'];
$date = $_GET['datum'];
$time = $_GET['uhrzeit'];
$number = $_GET['number'];

$coords = $_GET['coords'];
$coordParts = explode(',', $coords);
$longitude = $coordParts[0];
$latitude = $coordParts[1];

$zipcode = $_GET['zipcode'];
$city = $_GET['city'];
$street = $_GET['street'];

$hasParking = $_GET['hasParking'];
$hasBicycleParking = $_GET['hasBicycleParking'];
$hasLocalPublicTransport = $_GET['hasLocalPublicTransport'];
$hasPublicFacilities = $_GET['hasPublicFacilities'];
$hasLockerSystem = $_GET['hasLockerSystem'];
$hasTaxiRank = $_GET['hasTaxiRank'];
$hasTravelNecessities = $_GET['hasTravelNecessities'];
$hasSteplessAccess = $_GET['hasSteplessAccess'];
$hasWiFi = $_GET['hasWiFi'];
$hasTravelCenter = $_GET['hasTravelCenter'];
$hasRailwayMission = $_GET['hasRailwayMission'];
$hasDBLounge = $_GET['hasDBLounge'];
$hasLostAndFound = $_GET['hasLostAndFound'];
$hasCarRental = $_GET['hasCarRental'];

$formattedDate = date('ymd', strtotime($date));
$hour = date('H', strtotime($time));

require_once '../api_routes/timetable.php';
require_once '../api_routes/fasta_stations.php';
require_once '../helper_functions/xmlToJson.php';
require_once '../helper_functions/consoleLog.php';

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

    $timetableJson = xml_to_json($timetable, JSON_PRETTY_PRINT);
    $changesJson = xml_to_json($changes, JSON_PRETTY_PRINT);

    $timetableData = json_decode($timetableJson, true);
    $changesData = json_decode($changesJson, true);

    console_log($timetableJson);
    console_log($changesJson);

    // Create a mapping of changes by service ID
    $changesMap = [];
    if (isset($changesData['s'])) {
        // Ensure $changesData['s'] is an array
        $changesServices = is_array($changesData['s']) ? $changesData['s'] : [$changesData['s']];

        foreach ($changesServices as $service) {
            // Check if service has the necessary attributes
            if (!isset($service['attributes']['id'])) continue;

            $serviceId = $service['attributes']['id'];
            $changesMap[$serviceId] = [];

            // Check for arrival changes
            if (isset($service['ar']) && isset($service['ar']['attributes'])) {
                $arrivalAttributes = $service['ar']['attributes'];

                // In the changes mapping section, modify the date formatting
                if (isset($arrivalAttributes['ct'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Arrival Time',
                        'category' => $arrivalAttributes['ct'] ?? '',
                        'details' => $arrivalAttributes['cp'] ?? '',
                        'originalTime' => isset($arrivalAttributes['pt']) ?
                            substr($arrivalAttributes['pt'], -4, 2) . ':' . substr($arrivalAttributes['pt'], -2) : ''
                    ];
                }

                if (isset($arrivalAttributes['cs'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Arrival Status',
                        'category' => $arrivalAttributes['cs'] ?? '',
                        'details' => $arrivalAttributes['cp'] ?? ''
                    ];
                }

                if (isset($arrivalAttributes['cpth'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Arrival Route',
                        'category' => 'Route Change',
                        'details' => $arrivalAttributes['cpth'] ?? ''
                    ];
                }
            }

            // Check for departure changes
            if (isset($service['dp']) && isset($service['dp']['attributes'])) {
                $departureAttributes = $service['dp']['attributes'];

                // Similarly for departure
                if (isset($departureAttributes['ct'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Departure Time',
                        'category' => $departureAttributes['ct'] ?? '',
                        'details' => $departureAttributes['cp'] ?? '',
                        'originalTime' => isset($departureAttributes['pt']) ?
                            substr($departureAttributes['pt'], -4, 2) . ':' . substr($departureAttributes['pt'], -2) : ''
                    ];
                }

                if (isset($departureAttributes['cs'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Departure Status',
                        'category' => $departureAttributes['cs'] ?? '',
                        'details' => $departureAttributes['cp'] ?? ''
                    ];
                }

                if (isset($departureAttributes['cpth'])) {
                    $changesMap[$serviceId][] = [
                        'type' => 'Departure Route',
                        'category' => 'Route Change',
                        'details' => $departureAttributes['cpth'] ?? ''
                    ];
                }
            }

            // Remove empty change entries
            $changesMap[$serviceId] = array_filter($changesMap[$serviceId]);
        }
    }

    // Check if timetable is empty or false
    if ($timetableData === null) {
        $errorMessage = "Failed to parse timetable JSON.";
    } else {
        // Modify connections parsing
        $connections = [];
        // Ensure $timetableData['s'] is an array
        $services = is_array($timetableData['s']) ? $timetableData['s'] : [$timetableData['s']];

        foreach ($services as $service) {
            // Skip if service doesn't have attributes
            if (!isset($service['attributes']['id'])) continue;

            $connection = [
                'id' => $service['attributes']['id'],
                'train_line' => [
                    'type' => $service['tl']['attributes']['c'] ?? '',
                    'number' => $service['tl']['attributes']['n'] ?? '',
                    'operator' => $service['tl']['attributes']['o'] ?? ''
                ],
                'changes' => $changesMap[$service['attributes']['id']] ?? []
            ];

            // Check for arrival
            if (isset($service['ar']) && isset($service['ar']['attributes'])) {
                $connection['arrival'] = [
                    'platform' => $service['ar']['attributes']['pp'] ?? '',
                    'time' => $service['ar']['attributes']['pt'] ?? '',
                    'line' => $service['ar']['attributes']['l'] ?? '',
                    'route_before' => isset($service['ar']['attributes']['ppth']) ?
                        explode('|', $service['ar']['attributes']['ppth']) : []
                ];
            }

            // Check for departure
            if (isset($service['dp']) && isset($service['dp']['attributes'])) {
                $connection['departure'] = [
                    'platform' => $service['dp']['attributes']['pp'] ?? '',
                    'time' => $service['dp']['attributes']['pt'] ?? '',
                    'line' => $service['dp']['attributes']['l'] ?? '',
                    'route_after' => isset($service['dp']['attributes']['ppth']) ?
                        explode('|', $service['dp']['attributes']['ppth']) : []
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

        body {
            display: flex;
            flex-direction: column;
        }

        footer {
            width: 100%;
            height: 140px;
            background-color: var(--bs-secondary);
            color: var(--bs-light);
            margin-top: auto;
        }

        .route-clickable {
    cursor: pointer;
    transition: background-color 0.3s ease;
    padding: 5px;
    border-radius: 4px;
}

.route-clickable:hover {
    background-color: rgba(0,0,0,0.05);
}

.route-clickable:hover i {
    color: primary !important;
}
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    
<div id="loadingContainer" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Verbindungen werden geladen...</p>
    </div>
</div>
<main class="flex-grow-1">
    <!-- Hide this container when content is loaded -->
    <div id="connectionsContent" class="d-none">
        <!-- Your existing connections content -->
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-train me-2"></i>StationSync</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Startseite</a></li>
                </ul>
            </div>
    </nav>

    <h1>Zugverbindungen von <?php echo htmlspecialchars($station); ?></h1>

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

    <?php
    if (!empty($station2)) {
        $connections = array_filter($connections, function ($connection) use ($station2) {
            // Check if departure route_after exists
            if (isset($connection['departure']['route_after'])) {
                // Check if $station2 is in the route_after OR if any route_after station is in $station2
                return in_array($station2, $connection['departure']['route_after']) ||
                    array_reduce($connection['departure']['route_after'], function ($carry, $routeStation) use ($station2) {
                        return $carry || stripos($station2, $routeStation) !== false ||
                            stripos($routeStation, $station2) !== false;
                    }, false);
            }
            return false;
        });
    }
    ?>

    <?php if (!empty($connections)): ?>
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Zug</th>
                    <th>Gleis Ankunft</th>
                    <th>Zeit Ankunft</th>
                    <th>Von</th>
                    <th>Gleis Abfahrt</th>
                    <th>Zeit Abfahrt</th>
                    <th>Nach</th>
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
                            <?php
                            $arrivalPlatform = isset($connection['arrival']['platform']) ?
                                htmlspecialchars($connection['arrival']['platform']) : '';

                            // Check for platform changes
                            $platformChange = array_filter($connection['changes'], function ($change) {
                                return strpos($change['type'], 'Platform') !== false;
                            });

                            if (!empty($platformChange)) {
                                echo '<span class="text-decoration-line-through">' . $arrivalPlatform . '</span> ';
                                echo '<span class="text-danger">' . htmlspecialchars(end($platformChange)['details']) . '</span>';
                            } else {
                                echo $arrivalPlatform;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $arrivalTime = isset($connection['arrival']['time']) ?
                                htmlspecialchars(substr($connection['arrival']['time'], -4, 2) . ':' . substr($connection['arrival']['time'], -2)) : '';

                            // Check for time changes
                            $timeChange = array_filter($connection['changes'], function ($change) {
                                return $change['type'] === 'Arrival Time';
                            });

                            if (!empty($timeChange)) {
                                $change = end($timeChange);
                                if ($change['category'] > $connection['arrival']['time']) {
                                    $originalTimeFormatted = date('H:i', strtotime(substr($change['originalTime'], 0, 4) . '-' . substr($change['originalTime'], 4, 2) . '-' . substr($change['originalTime'], 6, 2) . ' ' . substr($change['originalTime'], -4)));
                                    echo '<span class="text-decoration-line-through">' . $arrivalTime . '</span> ';
                                    echo '<span class="text-danger">' . htmlspecialchars(substr($change['category'], -4, 2) . ':' . substr($change['category'], -2)) . '</span>';
                                    //echo htmlspecialchars(substr($change['category'], -4, 2) . ':' . substr($change['category'], -2));
                                } else {
                                    echo '<span">' . $arrivalTime . '</span> ';
                                }
                            } else {
                                echo $arrivalTime;
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (isset($connection['arrival']['route_before'])): ?>
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(reset($connection['arrival']['route_before'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Departure Information -->
                        <td>
                            <?php
                            $departurePlatform = isset($connection['departure']['platform']) ?
                                htmlspecialchars($connection['departure']['platform']) : '';

                            // Check for platform changes
                            $platformChange = array_filter($connection['changes'], function ($change) {
                                return strpos($change['type'], 'Platform') !== false;
                            });

                            if (!empty($platformChange)) {
                                echo '<span class="text-decoration-line-through">' . $departurePlatform . '</span> ';
                                echo '<span class="text-danger">' . htmlspecialchars(end($platformChange)['details']) . '</span>';
                            } else {
                                echo $departurePlatform;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $departureTime = isset($connection['departure']['time']) ?
                                htmlspecialchars(substr($connection['departure']['time'], -4, 2) . ':' . substr($connection['departure']['time'], -2)) : '';

                            // Check for time changes
                            $timeChange = array_filter($connection['changes'], function ($change) {
                                return $change['type'] === 'Departure Time';
                            });

                            if (!empty($timeChange)) {
                                $change = end($timeChange);
                                if ($change['category'] > $connection['departure']['time']) {
                                    $originalTimeFormatted = date('H:i', strtotime(substr($change['originalTime'], 0, 4) . '-' . substr($change['originalTime'], 4, 2) . '-' . substr($change['originalTime'], 6, 2) . ' ' . substr($change['originalTime'], -4)));
                                    echo '<span class="text-decoration-line-through">' . $departureTime . '</span> ';
                                    echo '<span class="text-danger">' . htmlspecialchars(substr($change['category'], -4, 2) . ':' . substr($change['category'], -2)) . '</span>';
                                    //echo htmlspecialchars(substr($change['category'], -4, 2) . ':' . substr($change['category'], -2));
                                } else {
                                    echo '<span">' . $departureTime . '</span> ';
                                }
                            } else {
                                echo $departureTime;
                            }

                            ?>
                        </td>
                        <td>
    <?php if (isset($connection['departure']['route_after'])): ?>
        <?php
        $routes = $connection['departure']['route_after'];
        $endStation = end($routes);
        $allStations = implode('->', $routes);
        ?>
        <div class="route text-truncate route-clickable" 
             data-bs-toggle="modal" 
             data-bs-target="#routeModal" 
             data-stations="<?php echo htmlspecialchars($allStations); ?>">
            <?php echo htmlspecialchars($endStation); ?>
            <i class="fas fa-route text-muted ms-2" data-bs-toggle="tooltip" title="Alle Stationen anzeigen"></i>
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
                            <td><?php echo isset($facility['description']) ? htmlspecialchars($facility['description']) : ''; ?></td>
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
    </main>

<!-- Route Modal -->
<div class="modal fade" id="routeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-map-marked-alt me-2"></i>Stationsroute
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul id="stationsList" class="list-group">
                    <!-- Stationen werden hier dynamisch eingefügt -->
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
  <div class="row g-0">
    <div class="col-md-6 d-flex">
      <iframe
        width="100%"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo ($longitude - 0.01); ?>,<?php echo ($latitude - 0.01); ?>,<?php echo ($longitude + 0.01); ?>,<?php echo ($latitude + 0.01); ?>&layer=mapnik"
        style="border: 1px solid #dee2e6; border-radius: 0.25rem;">
      </iframe>
    </div>
    <div class="col-md-6">
      <div class="card-body">
        <h5 class="card-title">Informationen</h5>
        <p class="card-text">
          <i class="fas fa-map-marker-alt me-2"></i>
          <strong>Adresse:</strong> <?php echo htmlspecialchars($zipcode); ?>, <?php echo htmlspecialchars($city); ?>, <?php echo htmlspecialchars($street); ?>
        </p>
        
        <?php
        $facilities = [
            'Parkplatz' => ['hasParking', 'fa-parking'],
            'Fahrradparkplatz' => ['hasBicycleParking', 'fa-bicycle'],
            'Öffentliche Verkehrsmittel' => ['hasLocalPublicTransport', 'fa-bus'],
            'Öffentliche Einrichtungen' => ['hasPublicFacilities', 'fa-restroom'],
            'Schließfachsystem' => ['hasLockerSystem', 'fa-lock'],
            'Taxi-Halteplatz' => ['hasTaxiRank', 'fa-taxi'],
            'Reisebedarf' => ['hasTravelNecessities', 'fa-shopping-bag'],
            'Barrierefreier Zugang' => ['hasSteplessAccess', 'fa-wheelchair'],
            'WiFi' => ['hasWiFi', 'fa-wifi'],
            'Reisezentrum' => ['hasTravelCenter', 'fa-info-circle'],
            'Eisenbahnmision' => ['hasRailwayMission', 'fa-cross'],
            'DB Lounge' => ['hasDBLounge', 'fa-couch'],
            'Fundbüro' => ['hasLostAndFound', 'fa-box-open'],
            'Autovermietung' => ['hasCarRental', 'fa-car']
        ];

        foreach ($facilities as $name => $data):
            $var = $data[0];
            $icon = $data[1];
            $isAvailable = ($$var == 'true' || $$var == '1' || $$var === true);
            $color = $isAvailable ? 'text-success' : 'text-danger';
        ?>
            <p class="card-text">
                <i class="fas <?php echo $icon; ?> me-2 <?php echo $color; ?>"></i>
                <strong><?php echo $name; ?></strong>
            </p>
        <?php
        endforeach;
        ?>
      </div>
    </div>
  </div>
</div>

    <!-- Der Footer -->
    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // When page loads, simulate loading
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate loading (remove this in production)
            setTimeout(function() {
                document.getElementById('loadingContainer').classList.add('d-none');
                document.getElementById('connectionsContent').classList.remove('d-none');
            }, 1500); // 1.5 seconds loading time
        });
        document.addEventListener('DOMContentLoaded', function() {
    var routeModal = document.getElementById('routeModal');
    routeModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var stations = button.getAttribute('data-stations').split('->');
        var stationsList = document.getElementById('stationsList');
        stationsList.innerHTML = '';
        
        stations.forEach(function(station, index) {
            var li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center';
            
            // Letztes Element anders stylen
            if (index === stations.length - 1) {
                li.innerHTML = `
                    <i class="fas fa-flag-checkered text-success me-2"></i>
                    <strong>${station}</strong>
                `;
            } else if (index === 0) {
                li.innerHTML = `
                    <i class="fas fa-play text-primary me-2"></i>
                    ${station}
                `;
            } else {
                li.innerHTML = `
                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                    ${station}
                `;
            }
            
            stationsList.appendChild(li);
        });
    });

    // Tooltips aktivieren
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
    </script>

</body>

</html>