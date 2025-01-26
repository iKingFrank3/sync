<?php
session_start();
include 'config.php'; // Ensure the database connection is included

// Get parameters from request
$locationId = $_GET['location_id'];
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Prepare the query with optional date filtering
$query = "SELECT sku_code, quantity, date_added FROM sku WHERE location_id = ?";
$params = array($locationId);
$types = "i";

if ($startDate) {
    $query .= " AND date_added >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if ($endDate) {
    $query .= " AND date_added <= ?";
    $params[] = $endDate;
    $types .= "s";
}

$stmt = $conn->prepare($query);

// Dynamically bind parameters using call_user_func_array
$params = array_merge(array($types), $params);
call_user_func_array(array($stmt, 'bind_param'), refValues($params));

$stmt->execute();

// Bind the result variables
$stmt->bind_result($sku_code, $quantity, $date_added);

// Fetch data
$skus = array();
while ($stmt->fetch()) {
    $skus[] = array(
        'sku_code' => $sku_code,
        'quantity' => $quantity,
        'date_added' => $date_added
    );
}

// Return data as JSON
echo json_encode($skus);

// Close connections
$stmt->close();
$conn->close();

// Helper function to pass parameters by reference
function refValues($arr) {
    $refs = array();
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}
?>
