<?php
// Set headers to allow cross-origin requests (if needed) and indicate JSON response
header('Content-Type: application/json');

// Include the database connection for this system (e.g., lgu2test's database)
require_once 'connection.php';

// Define the API key
$secret_api_key = '7b5e4c6f2a3a5f6c8d1c9e3e7f9e8a5b6d7a4f9c8d0a3e4f5c6b7e8d9a4b5c6f';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the raw POST data from the request body
$json_data = file_get_contents('php://input');

// Decode the JSON data into a PHP array
$data = json_decode($json_data, true);

// Check if the JSON decoding was successful and if required data is present
if ($data === null ||
    !isset($data['api_key']) ||
    $data['api_key'] !== $secret_api_key) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid API key.']);
    exit;
}
    
// Sanitize and prepare data for database insertion
$m9_SC_ID = (int)$data['m9_SC_ID'];
$m9_SC_Code = htmlspecialchars($data['m9_SC_Code']);
$date_created = htmlspecialchars($data['date_created']);
$measure_type = htmlspecialchars($data['measure_type']);
$measure_title = htmlspecialchars($data['measure_title']);
$measure_content = htmlspecialchars($data['measure_content']);
$introducers = htmlspecialchars($data['introducers']);
$measure_status = htmlspecialchars($data['measure_status']);
$checking_remarks = htmlspecialchars($data['checking_remarks']);
$checking_notes = htmlspecialchars($data['checking_notes']);
$checked_by = htmlspecialchars($data['checked_by']);
$datetime_submitted = htmlspecialchars($data['datetime_submitted']);

try {
    // Start a transaction for safe database operation
    $conn->begin_transaction();

    // First, insert the data that doesn't rely on auto-incremented ID
    $sql_insert = "INSERT INTO m6_measuredocketing_fromresearch (
        m9_SC_ID,
        m9_SC_Code,
        date_created,
        measure_type,
        measure_title,
        measure_content,
        introducers,
        measure_status,
        checking_remarks,
        checking_notes,
        checked_by,
        datetime_submitted
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql_insert);
    if ($stmt_insert === false) {
        throw new Exception("Error preparing INSERT statement: " . $conn->error);
    }
    
    $stmt_insert->bind_param("isssssssssss",
        $m9_SC_ID,
        $m9_SC_Code,
        $date_created,
        $measure_type,
        $measure_title,
        $measure_content,
        $introducers,
        $measure_status,
        $checking_remarks,
        $checking_notes,
        $checked_by,
        $datetime_submitted
    );

    if (!$stmt_insert->execute()) {
        throw new Exception("Error executing INSERT statement: " . $stmt_insert->error);
    }
    $new_id = $conn->insert_id;
    $stmt_insert->close();
    

    
    // Now, generate the new code using the auto-incremented ID
    $new_code = 'MD_' . str_pad($new_id, 3, '0', STR_PAD_LEFT);

    // Second, update the row with the newly generated m6_md_code
    $sql_update = "UPDATE m6_measuredocketing_fromresearch SET m6_md_code = ? WHERE m6_MD_ID = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) {
        throw new Exception("Error preparing UPDATE statement: " . $conn->error);
    }
    
    $stmt_update->bind_param("si", $new_code, $new_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Error executing UPDATE statement: " . $stmt_update->error);
    }
    $stmt_update->close();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Data received and saved successfully. New record ID: ' . $new_id . ', New code: ' . $new_code
    ]);

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>