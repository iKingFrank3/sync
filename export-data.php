<?php include 'assets/php/access.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <title>Export Data</title>
    <style>
        .small-datetime {
            width: 150px; /* Adjust the width as needed */
        }
        .actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px; /* Add some space between elements */
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <a href="dashboard.php">
                <img src="assets/img/italia_logo.png" alt="Italia Furniture Logo" class="logo">
            </a>
        </div>
        <div class="store-info">
            <h1>Export Data</h1>
            <div class="actions">
                <select id="locationDropdown" class="line-dropdown">
                    <option value="ATL1">ATL1</option>
                    <option value="ATL2">ATL2</option>
                    <option value="ATL3">ATL3</option>
                </select>
                <input type="datetime-local" id="startDateTime" class="line-input small-datetime">
                <input type="datetime-local" id="endDateTime" class="line-input small-datetime">
                <div>
                    <button class="action-button" onclick="filterData()">Submit</button>
                </div>
            </div>
        </div>
        <div class="item-table">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody id="exportTableBody">
                    <!-- Filtered table rows will be dynamically added here -->
                </tbody>
            </table>
        </div>
        <button class="action-button" onclick="exportToSpreadsheet()">Export to Spreadsheet</button>
        <div id="exportModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">&times;</span>
                <h2>Export Options</h2>
                <p>Select an option to export your data:</p>
                <button class="action-button" onclick="downloadCSV()">Download as CSV</button>
                <br>
                <button class="action-button" onclick="exportToGoogleDocs()">Export to Google Docs</button>
            </div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; 2023 Italia Furniture</p>
    </footer>
    <script src="https://apis.google.com/js/api.js"></script>
    <script>
        function fetchLocations() {
            fetch('assets/php/get_locations.php')
                .then(response => response.json())
                .then(locations => {
                    const locationDropdown = document.getElementById('locationDropdown');
                    locationDropdown.innerHTML = ''; // Clear existing options

                    locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location;
                        option.textContent = location;
                        locationDropdown.appendChild(option);
                    });

                    // Set initial location and load data
                    if (locations.length > 0) {
                        loadFilteredData(locations[0]);
                    }
                })
                .catch(error => console.error('Error fetching locations:', error));
        }

        function filterData() {
            const locationId = document.getElementById('locationDropdown').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;

            loadFilteredData(locationId, startDateTime, endDateTime);
        }

        function loadFilteredData(locationId, startDateTime = '', endDateTime = '') {
            const params = new URLSearchParams({
                location_id: locationId,
                start_date: startDateTime,
                end_date: endDateTime
            });

            fetch(`assets/php/get_skus.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('exportTableBody');
                    tableBody.innerHTML = ''; // Clear existing table data

                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${item.sku_code}</td><td>${item.quantity}</td><td>${item.date_added}</td>`;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error fetching filtered SKUs:', error));
        }

        // Auto-refresh every 60 seconds (60000 milliseconds)
        setInterval(() => {
            const locationId = document.getElementById('locationDropdown').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            loadFilteredData(locationId, startDateTime, endDateTime);
        }, 60000);

        function exportToSpreadsheet() {
            const modal = document.getElementById('exportModal');
            modal.style.display = 'block';
        }

        function closeModal() {
            const modal = document.getElementById('exportModal');
            modal.style.display = 'none';
        }

        function downloadCSV() {
            // Logic to download CSV
            const locationDropdown = document.getElementById('locationDropdown');
            const location = locationDropdown.options[locationDropdown.selectedIndex].text;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const tableBody = document.getElementById('exportTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            let csvContent = `Location: ${location}\n`;
            csvContent += `From: ${startDateTime} - ${endDateTime}\n\n`;
            csvContent += 'SKU,Quantity,Date Added\n';

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                const row = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent
                ].join(',');
                csvContent += row + '\n';
            }

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `export_${location}_${new Date().toISOString().slice(0, 10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            closeModal();
        }

        function handleClientLoad() {
            gapi.load('client:auth2', initClient);
        }

        function initClient() {
            gapi.client.init({
                apiKey: 'YOUR_API_KEY',
                clientId: 'YOUR_CLIENT_ID',
                discoveryDocs: ["https://www.googleapis.com/discovery/v1/apis/drive/v3/rest"],
                scope: 'https://www.googleapis.com/auth/drive.file'
            }).then(() => {
                // Listen for sign-in state changes.
                gapi.auth2.getAuthInstance().isSignedIn.listen(updateSigninStatus);

                // Handle the initial sign-in state.
                updateSigninStatus(gapi.auth2.getAuthInstance().isSignedIn.get());
            }).catch(error => {
                console.error('Error initializing Google API client:', error);
            });
        }

        function updateSigninStatus(isSignedIn) {
            if (isSignedIn) {
                exportToGoogleDocs();
            } else {
                gapi.auth2.getAuthInstance().signIn();
            }
        }

        function exportToGoogleDocs() {
            if (!gapi.auth2) {
                console.error('Google API client not initialized.');
                return;
            }

            const locationDropdown = document.getElementById('locationDropdown');
            const location = locationDropdown.options[locationDropdown.selectedIndex].text;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const tableBody = document.getElementById('exportTableBody');
            const rows = tableBody.getElementsByTagName('tr');

            let csvContent = `Location: ${location}\n`;
            csvContent += `From: ${startDateTime} - ${endDateTime}\n\n`;
            csvContent += 'SKU,Quantity,Date Added\n';

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                const row = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent
                ].join(',');
                csvContent += row + '\n';
            }

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const metadata = {
                'name': `export_${location}_${new Date().toISOString().slice(0, 10)}.csv`,
                'mimeType': 'text/csv'
            };

            const form = new FormData();
            form.append('metadata', new Blob([JSON.stringify(metadata)], { type: 'application/json' }));
            form.append('file', blob);

            const accessToken = gapi.auth2.getAuthInstance().currentUser.get().getAuthResponse().access_token;

            fetch('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', {
                method: 'POST',
                headers: new Headers({ 'Authorization': 'Bearer ' + accessToken }),
                body: form
            }).then(response => response.json())
              .then(file => {
                  const fileId = file.id;
                  const sheetsUrl = `https://docs.google.com/spreadsheets/d/${fileId}/edit`;
                  window.open(sheetsUrl, '_blank');
              })
              .catch(error => console.error('Error uploading to Google Drive:', error));

            closeModal();
        }

        // Fetch locations on page load
        document.addEventListener('DOMContentLoaded', fetchLocations);
    </script>
</body>
</html>
