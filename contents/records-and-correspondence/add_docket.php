<?php
require_once '../../auth.php';
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $measure_id = $_POST['measure_id'];
    $docket_number = $_POST['docket_number'];
    $category = $_POST['category'];
    $subjects = $_POST['subjects'];
    $remarks = $_POST['remarks'];
    $checked_by = "Records and Correspondence Section"; // Fixed value as requested
    $mfl_name = $_POST['mfl_name'] ?? ''; // Get MFL name if provided
    $mfl_feedback = $_POST['mfl_notes'] ?? ''; // Get MFL feedback if provided

    try {
        // Start transaction
        $conn->begin_transaction();

        // First get the measure details
        $getMeasureQuery = "SELECT * FROM m6_measuredocketing_fromresearch WHERE m6_MD_ID = ?";
        $stmtGet = $conn->prepare($getMeasureQuery);
        $stmtGet->bind_param("i", $measure_id);
        $stmtGet->execute();
        $measureResult = $stmtGet->get_result();
        $measureData = $measureResult->fetch_assoc();
        $stmtGet->close();

        if (!$measureData) {
            throw new Exception("Measure not found");
        }

        // Update the measure docketing record
        $updateQuery = "UPDATE m6_measuredocketing_fromresearch 
                       SET docket_no = ?, 
                           checked_by = ?,
                           category = ?,
                           `subject` = ?,
                           record_remarks = ?,
                           datetime_submitted = NOW()
                       WHERE m6_MD_ID = ?";

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssi", $docket_number, $checked_by, $category, $subjects, $remarks, $measure_id);
        $stmt->execute();
        $stmt->close();

        // Generate new MFL code
        $mflCodeQuery = "SELECT MAX(m3_MFL_ID) as last_id FROM m3_mflnotifications_forproposed";
        $mflResult = $conn->query($mflCodeQuery);
        $mflRow = $mflResult->fetch_assoc();
        $nextId = ($mflRow['last_id'] ?? 0) + 1;
        $mflCode = 'MFL_' . str_pad($nextId, 3, '0', STR_PAD_LEFT);        // Prepare data for sending to LGU-2
        $api_endpoint = 'http://localhost/LGU-2-main/contents/records-and-correspondence/receive_from_lgu2.php';
        $api_key = '7b5e4c6f2a3a5f6c8d1c9e3e7f9e8a5b6d7a4f9c8d0a3e4f5c6b7e8d9a4b5c6f';

        $data = array(
            'api_key' => $api_key,
            'm9_SC_ID' => $measureData['m9_SC_ID'],
            'm9_SC_Code' => $measureData['m9_SC_Code'],
            'date_created' => $measureData['date_created'],
            'measure_type' => $measureData['measure_type'],
            'measure_title' => $measureData['measure_title'],
            'measure_content' => $measureData['measure_content'],
            'introducers' => $measureData['introducers'],
            'measure_status' => $measureData['measure_status'],
            'checking_remarks' => $remarks,
            'checking_notes' => $measureData['checking_notes'],
            'checked_by' => $checked_by,
            'datetime_submitted' => date('Y-m-d H:i:s'),
            'docket_no' => $docket_number,
            'category' => $category,
            'subject' => $subjects
        );

        // Initialize cURL session
        $ch = curl_init($api_endpoint);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));

        // Execute cURL request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if the API call was successful
        if ($http_code !== 200) {
            throw new Exception("Failed to send data to LGU-2 system");
        }

        // If everything is successful, commit the transaction
        $conn->commit();

        header("Location: measure-docketing.php?success=1&message=" . urlencode("Docket number successfully added and sent to LGU-2"));
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if anything fails
        $conn->rollback();

        header("Location: measure-docketing.php?error=1&message=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
}

// If not POST request, redirect back to measure-docketing
header("Location: measure-docketing.php");
exit();
