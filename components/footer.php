<!-- footer.php -->
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

<!-- Kontakt Modal -->
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

<!-- Haftungsausschluss Modal -->
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

<!-- Datenschutz Modal -->
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