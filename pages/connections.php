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
    </style>
</head>

<body>

    <div id="loadingContainer" class="container text-center my-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Verbindungen werden geladen...</p>
    </div>

    <!-- Hide this container when content is loaded -->
    <div id="connectionsContent" class="d-none">
        <!-- Your existing connections content -->
    </div>

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
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(end($connection['departure']['route_after'])); ?>
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

    <iframe
        width="25%"
        height="250"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo ($longitude - 0.01); ?>,<?php echo ($latitude - 0.01); ?>,<?php echo ($longitude + 0.01); ?>,<?php echo ($latitude + 0.01); ?>&layer=mapnik"
        style="border: 1px solid black">
    </iframe>

    <div class="card" style="width: 25%; float: right; margin-left: 20px;">
        <div class="card-body">
            <h5 class="card-title">Informationen</h5>
            <p class="card-text"><strong>Postleitzahl:</strong> <?php echo htmlspecialchars($zipcode); ?></p>
            <p class="card-text"><strong>Stadt:</strong> <?php echo htmlspecialchars($city); ?></p>
            <p class="card-text"><strong>Straße:</strong> <?php echo htmlspecialchars($street); ?></p>
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
    </script>

</body>

</html>