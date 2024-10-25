# StationSync

StationSync is the website you want to use to get your next train.

## Mandatory requirements from school

- Aufrufbare Webseite (lokal ausreichend, also nicht auf Webserver)
- Grundgerüst aus HTML zum Aufbau der Seitenstruktur
- Eine Funktion, mit der man einen beliebigen deutschen Bahnhof aussuchen kann (Feld zum
Eintragen oder vergleichbares)
- Eine Funktion, bei der man ein Datum eingeben kann (Feld zum Eintragen oder vergleichbar)
- Eine Funktion, mit der man sich nun entweder die An- oder die Abfahrtszeiten an diesem
Bahnhof an diesem Datum anzeigen lassen kann (Uhrzeit, Zugart,
Herkunftsbahnhof/Zielbahnhof, Gleis) oder beides zusammen.
- Eine Funktion, die ausgibt, ob es an dem ausgesuchten Bahnhof einen Fahrstuhl zum Gleis
gibt.
- Mindestens eine rudimentäre CSS-Datei, welche im HTML eingebunden ist. Design ist aber
ausdrücklich NICHT Teil der Mindestanforderungen

## Used Deutsche Bahn API Endpoints

[StaDa - Station Data](https://developers.deutschebahn.com/db-api-marketplace/apis/product/stada)

[FaSta - Station Facilities Status](https://developers.deutschebahn.com/db-api-marketplace/apis/product/fasta)

[Timetables](https://developers.deutschebahn.com/db-api-marketplace/apis/product/timetables)

## How to get started

Set **CLIENT_ID** and **CLIENT_SECRET** inside the `.env` file with your own from [Deutsche Bahn API Marketplace](https://developers.deutschebahn.com/db-api-marketplace/apis/)