<?php
require_once '../../connection.php';
require_once 'AIClassifier.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_id']) && isset($_POST['correct_category'])) {
    $classifier = new AIClassifier($conn);
    $classifier->learnFromFeedback($_POST['document_id'], $_POST['correct_category']);

    // Set a session message
    session_start();
    $_SESSION['message'] = "Category updated successfully! The AI will learn from this feedback.";
}

// Redirect back to the previous page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
