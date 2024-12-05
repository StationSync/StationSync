<?php

$station =  $_GET['trainStation'];
$station2 =  $_GET['trainStation2'];
$evaNumber = $_GET['evaNumber'];
$date = $_GET['datum'];
$time = $_GET['uhrzeit'];
$number = $_GET['number'];
$longitude = 6.766979;
$latitude = 51.278517;

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

            // Check if messages exist
            if (isset($service['m'])) {
                // Ensure messages is an array
                $messages = is_array($service['m']) ? $service['m'] : [$service['m']];

                foreach ($messages as $message) {
                    // Check if message has the required attributes
                    if (!isset($message['attributes'])) continue;

                    $changesMap[$serviceId][] = [
                        'type' => $message['attributes']['t'] ?? '',
                        'category' => $message['attributes']['cat'] ?? '',
                        'from' => $message['attributes']['from'] ?? '',
                        'to' => $message['attributes']['to'] ?? ''
                    ];
                }
            }
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
                    <th>Änderungen</th>
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
                                htmlspecialchars(substr($connection['arrival']['time'], -4, 2) . ':' . substr($connection['arrival']['time'], -2)) : ''; ?></td>
                        <td>
                            <?php if (isset($connection['arrival']['route_before'])): ?>
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(reset($connection['arrival']['route_before'])); ?>
                                    <?php //echo htmlspecialchars(implode(' → ', $connection['arrival']['route_before'])); 
                                    ?>
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
                                htmlspecialchars(substr($connection['departure']['time'], -4, 2) . ':' . substr($connection['departure']['time'], -2)) : ''; ?></td>
                        <td>
                            <?php if (isset($connection['departure']['route_after'])): ?>
                                <div class="route text-truncate">
                                    <?php echo htmlspecialchars(end($connection['departure']['route_after'])); ?>
                                    <?php // echo htmlspecialchars(implode(' → ', $connection['departure']['route_after'])); 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($connection['changes'])): ?>
                                <?php foreach ($connection['changes'] as $change): ?>
                                    <div class="change">
                                        <?php if (!empty($change['category'])): ?>
                                            <strong><?php echo htmlspecialchars($change['category']); ?>:</strong>
                                        <?php endif; ?>
                                        <?php
                                        if (!empty($change['from']) && !empty($change['to'])) {
                                            $fromTime = substr($change['from'], -4, 2) . ':' . substr($change['from'], -2);
                                            $toTime = substr($change['to'], -4, 2) . ':' . substr($change['to'], -2);
                                            echo "Von $fromTime bis $toTime";
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Keine Änderungen
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
        width="100%"
        height="500"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo ($longitude - 0.01); ?>,<?php echo ($latitude - 0.01); ?>,<?php echo ($longitude + 0.01); ?>,<?php echo ($latitude + 0.01); ?>&layer=mapnik"
        style="border: 1px solid black">
    </iframe>

    <!-- Der Footer -->
    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>