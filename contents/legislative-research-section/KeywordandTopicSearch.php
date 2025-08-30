<?php
require_once '../../auth.php';
$pageTitle = "Keyword and Topic Search";
require_once '../../includes/header.php';

// Database connection
require_once '../../connection.php';
?>

<div class="cardish">
    <div class="container-fluid p-4">
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

            <?php /* Database queries will use the connection from above */ ?>

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
                                        
                                            <button type='button' class='btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#ITViewModal'
                                                data-title='" . htmlspecialchars($row['measure_title']) . "'
                                                data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                                data-content='" . nl2br(htmlspecialchars($row['measure_content'])) . "'>VIEW</button>

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
                                $sql_ordinances = "SELECT * FROM m9_proposedresolutions_copy";
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
                                    echo "<tr><td colspan='6'>No proposed resolutions found.</td></tr>";
                                }
                                // Close the statement for the third query
                                $stmt_ordinances->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../../includes/footer.php';
?>

   

