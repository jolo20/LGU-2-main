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
    
    if ($stmt->execute()) {
        // Redirect back to measure-docketing.php with success message
        header("Location: measure-docketing.php?success=1&message=" . urlencode("Docket number successfully added"));
        exit();
    } else {
        // Redirect back with error message
        header("Location: measure-docketing.php?error=1&message=" . urlencode("Error updating docket information"));
        exit();
    }
}

// If not POST request, redirect back to measure-docketing
header("Location: measure-docketing.php");
exit();
?>