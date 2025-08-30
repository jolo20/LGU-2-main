<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("192.168.0.38", "root", "", "lgu2");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => "Connection failed: " . $conn->connect_error]);
    exit;

try {
    // Fetch all documents without pagination
    $documentsQuery = "SELECT 
        md.docket_no,
        md.measure_title,
        md.measure_type,
        md.measure_status,
        md.date_created,
        cc.category_name,
        cc.classification_name,
        COALESCE(cc.tag_name, '') as tag_name
    FROM m6_measuredocketing md
    LEFT JOIN m6_categoryclassification cc ON md.m6_MD_ID = cc.measure_id
    ORDER BY md.date_created DESC";

    $documentsResult = $conn->query($documentsQuery);
    
    if (!$documentsResult) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $documents = [];
    while ($row = $documentsResult->fetch_assoc()) {
        // Ensure all expected fields are present
        $documents[] = [
            'docket_no' => $row['docket_no'] ?? '',
            'measure_title' => $row['measure_title'] ?? '',
            'measure_type' => $row['measure_type'] ?? '',
            'measure_status' => $row['measure_status'] ?? '',
            'date_created' => $row['date_created'] ?? '',
            'category_name' => $row['category_name'] ?? '',
            'classification_name' => $row['classification_name'] ?? '',
            'tag_name' => $row['tag_name'] ?? ''
        ];
    }

    echo json_encode($documents);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
