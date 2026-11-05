<?php
function zoekPlaats($plaats) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($plaats);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    // Nominatim eist een User-Agent, anders volgt er een foutmelding
    curl_setopt($ch, CURLOPT_USERAGENT, "PHP-kaart-opdracht/1.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $fout = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ["fout" => "Request mislukt: $fout"];
    }

    $data = json_decode($response, true);
    if (empty($data)) {
        return ["fout" => "Geen resultaat gevonden."];
    }

    // Eerste (beste) resultaat gebruiken
    return [
        "naam" => $data[0]["display_name"],
        "lat"  => (float) $data[0]["lat"],
        "lon"  => (float) $data[0]["lon"],
    ];
}

$plaats = $_GET["plaats"] ?? "Roermond";
$resultaat = zoekPlaats($plaats);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Kaart met marker</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #kaart { height: 500px; margin-top: 10px; }
    </style>
</head>
<body>
    <form method="get">
        <input type="text" name="plaats" value="<?= htmlspecialchars($plaats) ?>">
        <button type="submit">Zoek</button>
    </form>

    <?php if (isset($resultaat["fout"])): ?>
        <p><?= htmlspecialchars($resultaat["fout"]) ?></p>
    <?php else: ?>
        <p><?= htmlspecialchars($resultaat["naam"]) ?><br>
           Latitude: <?= $resultaat["lat"] ?>, Longitude: <?= $resultaat["lon"] ?></p>
        <div id="kaart"></div>
        <script>
            const lat = <?= json_encode($resultaat["lat"]) ?>;
            const lon = <?= json_encode($resultaat["lon"]) ?>;
            const naam = <?= json_encode($resultaat["naam"]) ?>;

            const kaart = L.map("kaart").setView([lat, lon], 13);
            L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "&copy; OpenStreetMap-bijdragers"
            }).addTo(kaart);
            L.marker([lat, lon]).addTo(kaart).bindPopup(naam).openPopup();
        </script>
    <?php endif; ?>
</body>
</html>