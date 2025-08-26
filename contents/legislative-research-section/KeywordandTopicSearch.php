<div id="alertContainer" class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999;"></div>

<?php
// START OF THE FIXED PHP BLOCK
session_start();
require_once '../../auth.php';
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_task'])) {
    
    // Sanitize and retrieve the form data
    $m9_SC_ID = isset($_POST['m9_SC_ID']) ? (int)$_POST['m9_SC_ID'] : 0;
    $m9_SC_Code = isset($_POST['m9_SC_Code']) ? htmlspecialchars($_POST['m9_SC_Code']) : '';
    $date_created = isset($_POST['date_created']) ? htmlspecialchars($_POST['date_created']) : '';
    $measure_type = isset($_POST['measure_type']) ? htmlspecialchars($_POST['measure_type']) : '';
    $title = isset($_POST['measure_title']) ? htmlspecialchars($_POST['measure_title']) : '';
    $content = isset($_POST['measure_content']) ? htmlspecialchars($_POST['measure_content']) : '';
    $introducers = isset($_POST['introducers']) ? htmlspecialchars($_POST['introducers']) : '';
    $measure_status = isset($_POST['measure_status']) ? htmlspecialchars($_POST['measure_status']) : '';
    $checking_remarks = isset($_POST['checking_remarks']) ? htmlspecialchars($_POST['checking_remarks']) : '';
    $checking_notes = isset($_POST['checking_notes']) ? htmlspecialchars($_POST['checking_notes']) : '';
    $checked_by = isset($_POST['checked_by']) ? htmlspecialchars($_POST['checked_by']) : '';
    $datetime_submitted = isset($_POST['datetime_submitted']) ? htmlspecialchars($_POST['datetime_submitted']) : '';

    try {
        // Prepare and execute the INSERT statement for m6_measuredocketing_fromresearch
        $insertSql = "INSERT INTO m6_measuredocketing_fromresearch (m9_SC_ID, m9_SC_Code, date_created, measure_type, measure_title, measure_content, introducers, measure_status, checking_remarks, checking_notes, checked_by, datetime_submitted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        if ($insertStmt === false) {
            throw new Exception('Error preparing INSERT statement: ' . $conn->error);
        }

        $insertStmt->bind_param("isssssssssss", $m9_SC_ID, $m9_SC_Code, $date_created, $measure_type, $title, $content, $introducers, $measure_status, $checking_remarks, $checking_notes, $checked_by, $datetime_submitted);

        if (!$insertStmt->execute()) {
            throw new Exception('Error executing INSERT statement: ' . $insertStmt->error);
        }
        $m6_md_id = $insertStmt->insert_id;
        $insertStmt->close();

        // Generate and update the m6_MD_Code
        $m6_MD_Code = 'MD_' . str_pad($m6_md_id, 3, '0', STR_PAD_LEFT);
        $sqlUpdateMdCode = "UPDATE m6_measuredocketing_fromresearch SET m6_MD_Code = ? WHERE m6_MD_ID = ?";
        $stmtUpdateMd = $conn->prepare($sqlUpdateMdCode);
        if ($stmtUpdateMd === false) {
            throw new Exception('Error preparing UPDATE statement: ' . $conn->error);
        }

        $stmtUpdateMd->bind_param("si", $m6_MD_Code, $m6_md_id);

        if (!$stmtUpdateMd->execute()) {
            throw new Exception('Error executing UPDATE statement: ' . $stmtUpdateMd->error);
        }
        $stmtUpdateMd->close();

        // Prepare and execute the UPDATE statement for m9_similaritychecking
        $sqlUpdateSc = "UPDATE m9_similaritychecking SET checking_remarks = ?, checking_notes = ?, checked_by = ?, datetime_submitted = ? WHERE m9_SC_ID = ?";
        $stmtUpdateSc = $conn->prepare($sqlUpdateSc);
        if ($stmtUpdateSc === false) {
            throw new Exception('Error preparing UPDATE m9_similaritychecking statement: ' . $conn->error);
        }
        $stmtUpdateSc->bind_param("ssssi", $checking_remarks, $checking_notes, $checked_by, $datetime_submitted, $m9_SC_ID);

        if (!$stmtUpdateSc->execute()) {
            throw new Exception('Error executing UPDATE m9_similaritychecking statement: ' . $stmtUpdateSc->error);
        }
        $stmtUpdateSc->close();

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Task successfully sent and updated!'];

    } catch (Exception $e) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Database Error: ' . $e->getMessage()];
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<?php
// REST OF THE CODE REMAINS THE SAME
$pageTitle = "Keyword and Topic Search";
require_once '../../includes/header.php';
?>



<style>
    /* Optional custom styles for minimalist design */
    body {
        background-color: #f8f9fa;
        font-family: Montserrat, system-ui, -apple-system, Arial, Helvetica, sans-serif;
    }
    .container {
        margin-top: 2rem;
    }
    .card {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    }
    .table th, .table td {
        vertical-align: middle;
    }
    .btn-action {
        width: 75px; /* Uniform button width */
        margin: 2px;
    }
</style>


<div class="container">
    <div class="card p-4">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming" type="button" role="tab" aria-controls="incoming" aria-selected="true">Incoming Task</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ordinances-tab" data-bs-toggle="tab" data-bs-target="#ordinances" type="button" role="tab" aria-controls="ordinances" aria-selected="false">Proposed Ordinances</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resolutions-tab" data-bs-toggle="tab" data-bs-target="#resolutions" type="button" role="tab" aria-controls="resolutions" aria-selected="false">Proposed Resolutions</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="incoming" role="tabpanel" aria-labelledby="incoming-tab">
                <h2 class="mt-4 mb-3">Incoming Task</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date Created</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Date & Time Submitted</th>
                                <th>Measure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query for the 'incoming' tab
                            $sql_incoming = "SELECT * FROM m9_similaritychecking";
                            $stmt_incoming = $conn->prepare($sql_incoming);
                            if ($stmt_incoming === false) {
                                die("Error preparing statement for incoming tasks: " . $conn->error);
                            }
                            if (!$stmt_incoming->execute()) {
                                die("Error executing statement for incoming tasks: " . $stmt_incoming->error);
                            }
                            $result_incoming = $stmt_incoming->get_result();
                            
                            if ($result_incoming->num_rows > 0) {
                                while ($row = $result_incoming->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['datetime_submitted']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>
                                    
                                        <button type='button' class='modal1 btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#ITViewModal'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-content='" . nl2br(htmlspecialchars($row['measure_content'], ENT_QUOTES, 'UTF-8')) . "'>VIEW</button>

                                        <button class='btn btn-success btn-sm btn-action send-task-btn' data-bs-toggle='modal' data-bs-target='#ITSendModal'
                                            data-id='" . htmlspecialchars($row['m9_SC_ID']) . "'
                                            data-idcode='" . htmlspecialchars($row['m9_SC_Code']) . "'
                                            data-date='" . htmlspecialchars($row['date_created']) . "'
                                            data-type='" . htmlspecialchars($row['measure_type']) . "'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-content='" . htmlspecialchars($row['measure_content']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-status='" . htmlspecialchars($row['measure_status']) . "'
                                            data-datetime='" . htmlspecialchars($row['datetime_submitted']) . "'>SEND</button>

                                        <button class='btn btn-danger btn-sm btn-action'>DELETE</button>
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No tasks found.</td></tr>";
                            }
                            // Close the statement for the first query
                            $stmt_incoming->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="ordinances" role="tabpanel" aria-labelledby="ordinances-tab">
                <h2 class="mt-4 mb-3">Proposed Ordinances</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Checking Remarks</th>
                                <th>Measure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query for the 'ordinances' tab
                            $sql_ordinances = "SELECT * FROM m9_proposedordinances_copy";
                            $stmt_ordinances = $conn->prepare($sql_ordinances);
                            if ($stmt_ordinances === false) {
                                die("Error preparing statement for ordinances: " . $conn->error);
                            }
                            if (!$stmt_ordinances->execute()) {
                                die("Error executing statement for ordinances: " . $stmt_ordinances->error);
                            }
                            $result_ordinances = $stmt_ordinances->get_result();

                            if ($result_ordinances->num_rows > 0) {
                                while ($row = $result_ordinances->fetch_assoc()) {
                                    echo "<tr>";
                                    // Display data from the new query
                                    echo "<td>" . htmlspecialchars($row['docket_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['checking_remarks']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>
                                            <button class='btn btn-primary btn-sm btn-action'>VIEW</button>
                                            <button class='btn btn-danger btn-sm btn-action'>DELETE</button>
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No proposed ordinances found.</td></tr>";
                            }
                            // Close the statement for the second query
                            $stmt_ordinances->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="tab-pane fade" id="resolutions" role="tabpanel" aria-labelledby="resolutions-tab">
                <h2 class="mt-4 mb-3">Proposed Resolutions</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Checking Remarks</th>
                                <th>Measure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query for the 'resolutions' tab
                            $sql_resolutions = "SELECT * FROM m9_proposedresolutions_copy";
                            $stmt_resolutions = $conn->prepare($sql_resolutions);
                            if ($stmt_resolutions === false) {
                                die("Error preparing statement for ordinances: " . $conn->error);
                            }
                            if (!$stmt_resolutions->execute()) {
                                die("Error executing statement for ordinances: " . $stmt_resolutions->error);
                            }
                            $result_resolutions = $stmt_resolutions->get_result();

                            if ($result_resolutions->num_rows > 0) {
                                while ($row = $result_resolutions->fetch_assoc()) {
                                    echo "<tr>";
                                    // Display data from the new query
                                    echo "<td>" . htmlspecialchars($row['docket_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['checking_remarks']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>
                                            <button class='btn btn-primary btn-sm btn-action'>VIEW</button>
                                            <button class='btn btn-danger btn-sm btn-action'>DELETE</button>
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No proposed resolutions found.</td></tr>";
                            }
                            // Close the statement for the third query
                            $stmt_resolutions->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ONLY CLOSE THE CONNECTION AT THE VERY END OF THE SCRIPT
// This is moved down here to allow queries for all three tabs
if ($conn->ping()) {
    $conn->close();
}
?>


<div class="modal fade" id="ITViewModal" tabindex="-1" aria-labelledby="ITViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="ITViewModalLabel">Measure Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="modal-title" class="form-label fw-bold">Title:</label>
                            <p id="modal-title" class="text-break"></p>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="mb-2">
                            <label for="modal-introducers" class="form-label fw-bold">Introducers:</label>
                            <p id="modal-introducers" class="text-break"></p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <label for="modal-content" class="form-label fw-bold">Measure Content:</label>
                    <div id="modal-content" class="p-3 border rounded bg-light text-break" style="max-height: 400px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ITViewModal = document.getElementById('ITViewModal');
        ITViewModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var title = button.getAttribute('data-title');
            var introducers = button.getAttribute('data-introducers');
            var content = button.getAttribute('data-content');

            // Update the modal's content.
            var modalTitle = ITViewModal.querySelector('#modal-title');
            var modalIntroducers = ITViewModal.querySelector('#modal-introducers');
            var modalContent = ITViewModal.querySelector('#modal-content');

            modalTitle.textContent = title;
            modalIntroducers.textContent = introducers;
            modalContent.innerHTML = content;
        });
    });
</script>


<div class="modal fade" id="ITSendModal" tabindex="-1" aria-labelledby="ITSendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="ITSendModalLabel">Send Measure to Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Title:</label>
                            <p id="modal-send-title-text" class="form-control-plaintext"></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label fw-bold">Introducers:</label>
                            <p id="modal-send-introducers-text" class="form-control-plaintext"></p>
                        </div>
                    </div>

                    <hr>
                    <div class="text-center">
                        <div class="mb-3">
                            <label for="checking_remarks" class="form-label">Checking Remarks</label>
                            <input type="text" class="form-control" id="checking_remarks" name="checking_remarks" required>
                        </div>

                        <div class="mb-3">
                            <label for="checking_notes" class="form-label">Checking Notes</label>
                            <textarea class="form-control" id="checking_notes" name="checking_notes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                        <label for="checked_by" class="form-label visually-hidden">Checked By</label>
                        <input type="hidden" id="checked_by" name="checked_by" value="Research Section">
                        </div>

                        <div class="mb-3">
                            <label for="datetime_submitted" class="form-label">Date & Time Submitted</label>
                            <input type="datetime-local" class="form-control" id="datetime_submitted" name="datetime_submitted" required>
                        </div>
                    </div>

                    
                    <input type="hidden" id="modal-send-id-hidden" name="m9_SC_ID">
                    <input type="hidden" id="modal-send-idcode-hidden" name="m9_SC_Code">
                    <input type="hidden" id="modal-send-date-hidden" name="date_created">
                    <input type="hidden" id="modal-send-type-hidden" name="measure_type">
                    <input type="hidden" id="modal-send-title-hidden" name="measure_title">
                    <input type="hidden" id="modal-send-content-hidden" name="measure_content">
                    <input type="hidden" id="modal-send-introducers-hidden" name="introducers">
                    <input type="hidden" id="modal-send-status-hidden" name="measure_status">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="send_task" class="btn btn-success">Send Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ITSendModal = document.getElementById('ITSendModal');
    ITSendModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var idcode = button.getAttribute('data-idcode');
        var date = button.getAttribute('data-date');
        var type = button.getAttribute('data-type');
        var title = button.getAttribute('data-title');
        var content = button.getAttribute('data-content');
        var introducers = button.getAttribute('data-introducers');
        var status = button.getAttribute('data-status');

        // Populate hidden fields for form submission
        ITSendModal.querySelector('#modal-send-id-hidden').value = id;
        ITSendModal.querySelector('#modal-send-idcode-hidden').value = idcode;
        ITSendModal.querySelector('#modal-send-date-hidden').value = date;
        ITSendModal.querySelector('#modal-send-type-hidden').value = type;
        ITSendModal.querySelector('#modal-send-title-hidden').value = title;
        ITSendModal.querySelector('#modal-send-content-hidden').value = content;
        ITSendModal.querySelector('#modal-send-introducers-hidden').value = introducers;
        ITSendModal.querySelector('#modal-send-status-hidden').value = status;
        
        // Display the data in the text labels
        ITSendModal.querySelector('#modal-send-title-text').textContent = title;
        ITSendModal.querySelector('#modal-send-introducers-text').textContent = introducers;
        
        // Populate the Date & Time input field with the CURRENT local date and time
        const now = new Date();
        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        const day = now.getDate().toString().padStart(2, '0');
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        
        const currentDatetime = `${year}-${month}-${day}T${hours}:${minutes}`;
        ITSendModal.querySelector('#datetime_submitted').value = currentDatetime;
    });
});
</script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const alertContainer = document.getElementById('alertContainer');

    // PHP to JS Bridge to check for session message
    const message = <?php echo json_encode(isset($_SESSION['message']) ? $_SESSION['message'] : null); ?>;

    if (message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${message.type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.textContent = message.text;

        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('type', 'button');
        closeBtn.setAttribute('data-bs-dismiss', 'alert');
        closeBtn.setAttribute('aria-label', 'Close');

        alertDiv.appendChild(closeBtn);
        alertContainer.appendChild(alertDiv);

        // Remove the alert after a few seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000); // Alert will disappear after 5 seconds
        
        <?php unset($_SESSION['message']); ?>
    }
});
</script>

<?php
require_once '../../includes/footer.php';
?>