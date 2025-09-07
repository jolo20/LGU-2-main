<?php
require_once '../../auth.php';
require_once '../../connection.php';
require_once 'DocumentTracker.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $measure_id = $_POST['measure_id'] ?? '';
    $departments = $_POST['department'] ?? [];
    $notes = $_POST['notes'] ?? '';

    if (empty($measure_id) || empty($departments)) {
        $_SESSION['error'] = "Missing required information for assignment.";
        header("Location: document-tracking.php?tab=accomplished");
        exit;
    }

    // Convert array of departments to comma-separated string
    $department_string = implode(',', $departments);

    try {
        // Update the document assignment
        $stmt = $conn->prepare("UPDATE m6_measures SET 
            assigned_to = ?,
            assignment_notes = ?,
            assignment_date = NOW()
            WHERE measure_id = ?");

        $stmt->bind_param("sss", $department_string, $notes, $measure_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Document successfully assigned to department.";
            header("Location: document-tracking.php?tab=accomplished");
        } else {
            throw new Exception("Error updating assignment");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    // Redirect back to document tracking
    header("Location: document-tracking.php?tab=accomplished");
    exit;
} else {
    // If not a POST request, redirect to the main page
    header("Location: document-tracking.php?tab=accomplished");
    exit;
}
?>