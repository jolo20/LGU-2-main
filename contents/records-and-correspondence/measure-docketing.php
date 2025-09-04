<?php
require_once '../../auth.php';
$pageTitle = "Measure Docketing";
require_once '../../includes/header.php';

require_once 'connection.php';

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = ' WHERE (m.docket_no IS NULL OR m.docket_no = "")';
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $searchCondition .= " AND m.measure_title LIKE '%$searchTerm%'";
}

// Get suggestions for the search input
$suggestions = [];
if (!empty($searchTerm)) {
    $suggestQuery = "SELECT DISTINCT measure_title FROM m6_measuredocketing_fromresearch WHERE measure_title LIKE '%$searchTerm%'";
    $suggestResult = $conn->query($suggestQuery);
    while ($row = $suggestResult->fetch_assoc()) {
        $suggestions[] = $row['measure_title'];
    }
}

$checkEmpty = $conn->query("SELECT COUNT(*) as count FROM m6_measuredocketing_fromresearch");
$row = $checkEmpty->fetch_assoc();

// Pagination setup
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = 5;
$offset = ($page - 1) * $limit;

// Clean up the current URL parameters
$params = $_GET;
unset($params['page']); // Remove page from the base params

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM m6_measuredocketing_fromresearch m $searchCondition";
$totalResult = $conn->query($countQuery);
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'];
$totalPages = ceil($total / $limit);

// Main query with pagination
$sql = "SELECT m.m6_MD_ID, m.m6_MD_Code, m.measure_title, m.measure_content,
               m.date_created, m.measure_type, m.measure_status,
               m.checking_remarks, m.checking_notes, m.checked_by,
               m.datetime_submitted, m.introducers, m.docket_no,
               m.MFL_Name, m.MFL_Feedback
        FROM m6_measuredocketing_fromresearch m
        $searchCondition
        ORDER BY m.datetime_submitted DESC
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>

<!-- Start content wrapper -->
<div class="cardish">
    <div class="d-flex align-items-center gap-2 mb-4">
        <h1 class="mb-0">Measure Docketing</h1>
    </div>

    <!-- Search Form -->
    <div class="mb-4 d-flex justify-content-end">
        <form method="GET" class="search-form" id="searchForm">
            <div class="position-relative" style="max-width: 300px; width: 100%;">
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" placeholder="Search measures..."
                        name="search" id="searchInput" autocomplete="off"
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <span class="input-group-text bg-white">
                        <i class="fa-solid fa-search text-muted"></i>
                    </span>
                </div>
                <div id="searchSuggestions" class="position-absolute w-100 mt-1 shadow-sm d-none"
                    style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                </div>
            </div>
        </form>
    </div>

    <style>
        #searchSuggestions {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        #searchSuggestions .suggestion {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f8f9fa;
        }

        #searchSuggestions .suggestion:hover {
            background-color: #f8f9fa;
        }

        #searchInput:focus {
            box-shadow: none;
            border-color: var(--brand);
        }
    </style>
    <div class="card mb-3">
        <div class="card-header text-white d-flex justify-content-between align-items-center"
            style="background:var(--brand)">
            Documents
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-striped table-hover align-middle mb-0">
                <thead class="text-white" style="background:var(--brand)">
                    <tr>
                        <th>Date Created</th>
                        <th>Title</th>
                        <th>Introducers</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Checking Remarks</th>
                        <th>Checked By</th>
                        <th>Date Submitted</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= date("m/d/Y", strtotime($row["date_created"])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row["measure_title"]); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row["introducers"]); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row["measure_type"]); ?>
                                </td>
                                <td>
                                    <?= ucfirst(htmlspecialchars($row["measure_status"])); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row["checking_remarks"]); ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row["checked_by"]); ?>
                                </td>
                                <td>
                                    <?= date("m/d/Y h:i A", strtotime($row["datetime_submitted"])); ?>
                                </td>
                                <td class="text-center">
                                    <!-- Action Buttons -->
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#measureModal<?= $row['m6_MD_ID'] ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#assignCommitteeModal<?= $row['m6_MD_ID'] ?>" title="Assign Committee">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                            data-bs-target="#replyModal<?= $row['m6_MD_ID'] ?>" title="Reply">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal"
                                            data-bs-target="#sendMFLModal<?= $row['m6_MD_ID'] ?>" title="Send to MFL">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                                <!-- Table cell end -->
                                </td>
                            </tr>
        </div>
    </div>

    <!-- Assign Committee Modal -->
    <div class="modal fade" id="assignCommitteeModal<?= $row['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Committee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="assign_committee.php" method="POST">
                        <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Select Committee</label>
                            <select class="form-select" name="committee_id" required>
                                <option value="">Choose a committee...</option>
                                <option value="1">Committee on Rules</option>
                                <option value="2">Committee on Finance</option>
                                <option value="3">Committee on Infrastructure</option>
                                <!-- Add more committees as needed -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="assignment_notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Assign Committee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal<?= $row['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reply to Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="send_reply.php" method="POST">
                        <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Reply Message</label>
                            <textarea class="form-control" name="reply_message" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Update</label>
                            <select class="form-select" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_review">In Review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info text-white">Send Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Send to MFL Modal -->
    <div class="modal fade" id="sendMFLModal<?= $row['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send to MFL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="send_to_mfl.php" method="POST">
                        <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                        <div class="mb-3">
                            <label class="form-label">MFL Name</label>
                            <input type="text" class="form-control" name="mfl_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority Level</label>
                            <select class="form-select" name="priority_level">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes for MFL</label>
                            <textarea class="form-control" name="mfl_notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning text-dark">Send to MFL</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="9" class="text-center">No records found</td>
    </tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Modal Container - All modals moved outside of table -->
<?php if ($result->num_rows > 0):
    $result->data_seek(0); // Reset result pointer
    while ($row = $result->fetch_assoc()): ?>

        <!-- Measure Details Modal -->
        <div class="modal fade" id="measureModal<?= $row['m6_MD_ID'] ?>" tabindex="-1"
            aria-labelledby="measureModalLabel<?= $row['m6_MD_ID'] ?>" aria-hidden="true" data-bs-backdrop="static">
            <style>
                #measureModal<?= $row['m6_MD_ID'] ?>.modal-dialog {
                    margin-top: 3vh;
                }

                #measureModal<?= $row['m6_MD_ID'] ?>.modal-header {
                    background: var(--brand) !important;
                }

                #measureModal<?= $row['m6_MD_ID'] ?>.btn-primary {
                    background: var(--brand);
                    border-color: var(--brand);
                }

                #measureModal<?= $row['m6_MD_ID'] ?>.btn-primary:hover {
                    background: var(--brand-dark, #0056b3);
                    border-color: var(--brand-dark, #0056b3);
                }
            </style>
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: #2B4B82;">
                        <h5 class="modal-title" id="measureModalLabel<?= $row['m6_MD_ID'] ?>">Measure Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body details">
                        <div class="row">
                            <style>
                                .measure-details {
                                    height: 720px;
                                    overflow-y: auto;
                                    scrollbar-width: auto;
                                    scrollbar-color: var(--brand) #ffffff;
                                }

                                .measure-details::-webkit-scrollbar {
                                    width: 8px;
                                }

                                .measure-details::-webkit-scrollbar-track {
                                    background: #ffffff;
                                }

                                .measure-details::-webkit-scrollbar-thumb {
                                    background-color: var(--brand);
                                    border-radius: 10px;
                                    border: 2px solid #ffffff;
                                }
                            </style>
                            <!-- Measure Details Column -->
                            <div class="col-md-6 measure-details">
                                <h6 class="border-bottom pb-2 mb-3 sticky-top bg-white">Measure
                                    Information</h6>
                                <p><strong>Title:</strong>
                                    <?= htmlspecialchars($row["measure_title"]) ?>
                                </p>
                                <p><strong>Content:</strong>
                                <p style="text-align: center;">
                                    <?= nl2br(htmlspecialchars($row["measure_content"], ENT_QUOTES, 'UTF-8')) ?>
                                </p>
                                </p>
                                <p><strong>Date Created:</strong>
                                    <?= date("m/d/Y", strtotime($row["date_created"])) ?>
                                    
                                </p>
                                <p><strong>Status:</strong>
                                <div class="text-nowrap">
                                    <?= ucfirst($row["measure_status"]) ?>
                                    <div class="text-muted small">
                                    <?= date('M d, Y', strtotime($row['date_created'])) ?>
                                    </div>
                                </div>
                                </p>
                                <p><strong>Introducers:</strong>
                                    <?= htmlspecialchars($row["introducers"]) ?>
                                </p>
                                <p><strong>Submitted:</strong>
                                    <?= date("m/d/Y h:i A", strtotime($row["datetime_submitted"])) ?>
                                </p>

                                <?php if (isset($row["docket_no"]) && !empty($row["docket_no"])): ?>
                                    <h6 class="border-bottom pb-2 mb-3 mt-4">Current Docket Information
                                    </h6>
                                    <p><strong>Docket Number:</strong>
                                        <?= htmlspecialchars($row["docket_no"] ?? '') ?>
                                    </p>
                                    <p><strong>Committee/Office:</strong>
                                        <?= htmlspecialchars($row["MFL_Name"] ?? '') ?>
                                    </p>
                                    <p><strong>Feedback:</strong>
                                        <?= htmlspecialchars($row["MFL_Feedback"] ?? '') ?>
                                    </p>
                                    <p><strong>Checking Remarks:</strong>
                                        <?= htmlspecialchars($row["checking_remarks"]) ?>
                                    </p>
                                    <p><strong>Checking Notes:</strong>
                                        <?= htmlspecialchars($row["checking_notes"]) ?>
                                    </p>
                                    <p><strong>Checked By:</strong>
                                        <?= htmlspecialchars($row["checked_by"]) ?>
                                    </p>
                                <?php endif; ?>
                                <!-- File Upload Section -->
                                <div class="mt-4">
                                    <h6 class="border-bottom pb-2 mb-3">Attached Files</h6>
                                    <form action="upload_file.php" method="POST" enctype="multipart/form-data" class="mb-3">
                                        <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                                        <div class="mb-3">
                                            <label for="file_<?= $row['m6_MD_ID'] ?>" class="form-label">Upload Document File</label>
                                            <input type="file" class="form-control" id="file_<?= $row['m6_MD_ID'] ?>" name="document_file" required>
                                        </div>
                                    </form>

                                    <!-- Display existing files if any -->
                                    <div class="list-group">
                                        <?php
                                        // Fetch files for this measure
                                        $filesSql = "SELECT * FROM measure_files WHERE measure_id = ?";
                                        $filesStmt = $conn->prepare($filesSql);
                                        $filesStmt->bind_param("i", $row['m6_MD_ID']);
                                        $filesStmt->execute();
                                        $filesResult = $filesStmt->get_result();

                                        while ($file = $filesResult->fetch_assoc()) {
                                            echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                            echo '<div>';
                                            echo '<i class="fas fa-file me-2"></i>';
                                            echo htmlspecialchars($file['file_name']);
                                            if ($file['description']) {
                                                echo '<br><small class="text-muted">' . htmlspecialchars($file['description']) . '</small>';
                                            }
                                            echo '</div>';
                                            echo '<div class="btn-group">';
                                            echo '<a href="download_file.php?id=' . $file['id'] . '" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>';
                                            echo '<button onclick="deleteFile(' . $file['id'] . ')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <style>
                                .section-form {
                                    overflow-y: auto;
                                    scrollbar-width: auto;
                                    scrollbar-color: var(--brand) #ffffff;
                                }

                                .section-form::-webkit-scrollbar {
                                    width: 8px;
                                }

                                .section-form::-webkit-scrollbar-track {
                                    background: #ffffff;
                                }

                                .section-form::-webkit-scrollbar-thumb {
                                    background-color: var(--brand);
                                    border-radius: 10px;
                                    border: 2px solid #ffffff;
                                }
                            </style>
                            <!-- Add Docket Form Column -->
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Add Docket</h6>
                                <form class="docket-form" method="POST">
                                    <input type="hidden" name="measure_id"
                                        value="<?= $row['m6_MD_ID'] ?>">
                                    <div class="mb-3">
                                        <label for="docket_number_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">Docket Number</label>
                                        <input type="text" class="form-control"
                                            id="docket_number_<?= $row['m6_MD_ID'] ?>"
                                            name="docket_number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="doc_type_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">Document Type</label>
                                        <select class="form-select"
                                            id="doc_type_<?= $row['m6_MD_ID'] ?>" name="doc_type">
                                            <option value="ordinance" <?= strtolower($row['measure_type']) === 'ordinance' ? 'selected' : '' ?>>Ordinance</option>
                                            <option value="resolution" <?= strtolower($row['measure_type']) === 'resolution' ? 'selected' : '' ?>>Resolution</option>
                                        </select>

                                    </div>
                                    <!-- Ordinance Categories -->
                                    <div class="mb-3" id="categoryDiv_<?= $row['m6_MD_ID'] ?>"
                                        style="display: none;">
                                        <label for="category_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">Category</label>
                                        <select class="form-select"
                                            id="category_<?= $row['m6_MD_ID'] ?>" name="category">
                                            <option value="">Select Category</option>
                                            <option value="general welfare">General Welfare</option>
                                            <option value="administrative">Administrative</option>
                                            <option value="health and sanitation">Health and Sanitation</option>
                                            <option value="education">Education</option>
                                            <option value="culture">Culture</option>
                                            <option value="sports">Sports</option>
                                            <option value="taxation">Taxation</option>
                                            <option value="revenue and appropriations">Revenue & Appropriations</option>
                                            <option value="peace and order">Peace and Order</option>
                                            <option value="public safety">Public Safety</option>
                                            <option value="infrastructure">Infrastructure and Public Works</option>
                                            <option value="environment and natural resources">Environment and Natural Resources</option>
                                            <option value="social services and welfare">Social Services and Welfare</option>
                                        </select>
                                    </div>
                                    <!-- Resolution Subjects -->
                                    <div class="mb-3" id="subjectDiv_<?= $row['m6_MD_ID'] ?>"
                                        style="display: none;">
                                        <label for="subject_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">Category</label>
                                        <select class="form-select"
                                            id="subject_<?= $row['m6_MD_ID'] ?>" name="subjects">
                                            <option value="">Select Subject</option>
                                            <option value="commendatory">Commendatory</option>
                                            <option value="congratulatory">Congratulatory</option>
                                            <option value="authorizing">Authorizing</option>
                                            <option value="approving">Approving</option>
                                            <option value="expressing Support">Expressing Support
                                            </option>
                                            <option value="position">Position</option>
                                            <option value="request">Request</option>
                                            <option value="appeal">Appeal</option>
                                            <option value="condolence">Condolence</option>
                                            <option value="sympathy">Sympathy</option>
                                            <option value="administrative">Administrative</option>
                                            <option value="organizational">Organizational</option>
                                        </select>
                                    </div>
                                    <script>
                                        (function() {
                                            function update(id) {
                                                const docType = document.getElementById('doc_type_' + id);
                                                const categoryDiv = document.getElementById('categoryDiv_' + id);
                                                const subjectDiv = document.getElementById('subjectDiv_' + id);
                                                const categorySelect = document.getElementById('category_' + id);
                                                const subjectSelect = document.getElementById('subject_' + id);
                                                if (!docType) return;

                                                const docTypeValue = docType.value.toLowerCase();

                                                if (docTypeValue.includes('ordinance')) {
                                                    if (categoryDiv) categoryDiv.style.display = 'block';
                                                    if (subjectDiv) subjectDiv.style.display = 'none';
                                                    if (categorySelect) categorySelect.required = true;
                                                    if (subjectSelect) {
                                                        subjectSelect.required = false;
                                                        subjectSelect.value = '';
                                                    }
                                                } else if (docTypeValue.includes('resolution')) {
                                                    if (categoryDiv) categoryDiv.style.display = 'none';
                                                    if (subjectDiv) subjectDiv.style.display = 'block';
                                                    if (categorySelect) {
                                                        categorySelect.required = false;
                                                        categorySelect.value = '';
                                                    }
                                                    if (subjectSelect) subjectSelect.required = true;
                                                } else {
                                                    if (categoryDiv) categoryDiv.style.display = 'none';
                                                    if (subjectDiv) subjectDiv.style.display = 'none';
                                                    if (categorySelect) {
                                                        categorySelect.required = false;
                                                        categorySelect.value = '';
                                                    }
                                                    if (subjectSelect) {
                                                        subjectSelect.required = false;
                                                        subjectSelect.value = '';
                                                    }
                                                }
                                            }

                                            document.addEventListener('DOMContentLoaded', function() {
                                                // Initial update for all document type inputs
                                                document.querySelectorAll('input[id^="doc_type_"]').forEach(function(input) {
                                                    const id = input.id.replace('doc_type_', '');
                                                    update(id);
                                                });

                                                // Re-check when modal is shown (Bootstrap)
                                                document.querySelectorAll('.modal').forEach(function(modal) {
                                                    modal.addEventListener('shown.bs.modal', function() {
                                                        const input = modal.querySelector('input[id^="doc_type_"]');
                                                        if (input) {
                                                            const id = input.id.replace('doc_type_', '');
                                                            update(id);
                                                        }
                                                    });
                                                });
                                            });
                                        })();
                                    </script>
                                    <div class="mb-3">
                                        <label for="from_dept_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">From</label>
                                        <input type="text" class="form-control"
                                            id="from_dept_<?= $row['m6_MD_ID'] ?>"
                                            value="<?= $row['checked_by'] ?>">
                                        <input type="hidden" name="from_dept"
                                            value="<?= $row['checked_by'] ?>">
                                    </div>
                                    <style>
                                        .modal {
                                            scrollbar-width: auto;
                                            scrollbar-color: var(--brand) #ffffff;
                                        }

                                        .modal::-webkit-scrollbar {
                                            width: 8px;
                                        }

                                        .modal::-webkit-scrollbar-track {
                                            background: #ffffff;
                                        }

                                        .modal::-webkit-scrollbar-thumb {
                                            background-color: var(--brand);
                                            border-radius: 10px;
                                            border: 2px solid #ffffff;
                                        }

                                        /* Move modal position down slightly */
                                        .modal-dialog {
                                            margin-top: 10vh !important;
                                            /* This will move it down by 2% of the viewport height */
                                        }
                                    </style>
                                    <div class="mb-3">
                                        <label for="to_dept_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">To (Select Multiple)</label>
                                        <div class="form-control p-0 section-form"
                                            style="max-height: 100px; overflow-y: auto;">
                                            <div class="list-group list-group-flush">
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="ordinance_resolution_section">
                                                    Ordinance & Resolution Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="minutes_section">
                                                    Minutes Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="event_calendar_section">
                                                    Event Calendar Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="committee_management">
                                                    Committee Management System
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="journal_section">
                                                    Journal Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="public_hearing">
                                                    Public Hearing Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="archive_section">
                                                    Reference and Archive Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="research_section">
                                                    Legislative Research Section
                                                </label>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1"
                                                        type="checkbox" name="to_dept[]"
                                                        value="public_consultation">
                                                    Public Consultation Management
                                                </label>
                                            </div>
                                        </div>
                                        <small class="text-muted">Check all departments that
                                            apply</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="remarks_<?= $row['m6_MD_ID'] ?>"
                                            class="form-label">Remarks</label>
                                        <textarea class="form-control"
                                            id="remarks_<?= $row['m6_MD_ID'] ?>" name="remarks"
                                            rows="3"></textarea>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-success">Accomplish</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Committee Modal -->
        <div class="modal fade" id="assignCommitteeModal<?= $row['m6_MD_ID'] ?>" tabindex="-1"
            aria-labelledby="assignCommitteeModalLabel<?= $row['m6_MD_ID'] ?>" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="assignCommitteeModalLabel<?= $row['m6_MD_ID'] ?>">Assign Committee</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="assign_committee.php" method="POST">
                            <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Select Committee</label>
                                <select class="form-select" name="committee_id" required>
                                    <option value="">Choose a committee...</option>
                                    <option value="1">Committee on Rules</option>
                                    <option value="2">Committee on Finance</option>
                                    <option value="3">Committee on Infrastructure</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="assignment_notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Assign Committee</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div class="modal fade" id="replyModal<?= $row['m6_MD_ID'] ?>" tabindex="-1"
            aria-labelledby="replyModalLabel<?= $row['m6_MD_ID'] ?>" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="replyModalLabel<?= $row['m6_MD_ID'] ?>">Reply to Document</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="send_reply.php" method="POST">
                            <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Reply Message</label>
                                <textarea class="form-control" name="reply_message" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Update</label>
                                <select class="form-select" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="in_review">In Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info text-white">Send Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send to MFL Modal -->
        <div class="modal fade" id="sendMFLModal<?= $row['m6_MD_ID'] ?>" tabindex="-1"
            aria-labelledby="sendMFLModalLabel<?= $row['m6_MD_ID'] ?>" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="sendMFLModalLabel<?= $row['m6_MD_ID'] ?>">Send to MFL</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="send_to_mfl.php" method="POST">
                            <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                            <div class="mb-3">
                                <label class="form-label">MFL Name</label>
                                <input type="text" class="form-control" name="mfl_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority Level</label>
                                <select class="form-select" name="priority_level">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes for MFL</label>
                                <textarea class="form-control" name="mfl_notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning text-dark">Send to MFL</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>
</div>
<?php if ($totalPages > 1): ?>
    <!-- Pagination Controls -->
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= ($page - 1) ?>&<?= http_build_query($params) ?>"
                            aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($params) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= ($page + 1) ?>&<?= http_build_query($params) ?>"
                            aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<div class="text-center mt-2 text-muted small">
    Showing
    <?= ($offset + 1) ?>-
    <?= min($offset + $limit, $total) ?> of
    <?= $total ?> documents
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');

        // Initialize suggestions from PHP
        const suggestions = <?= json_encode($suggestions) ?>;
        if (suggestions.length > 0) {
            showSuggestions(suggestions);
        }

        function showSuggestions(suggestions) {
            if (suggestions.length > 0) {
                const suggestionHtml = suggestions
                    .map(text => `
                        <div class="suggestion p-2 border-bottom">
                            <div class="text-truncate">${text}</div>
                        </div>
                    `)
                    .join('');
                searchSuggestions.innerHTML = suggestionHtml;
                searchSuggestions.classList.remove('d-none');
            } else {
                searchSuggestions.classList.add('d-none');
            }
        }

        // Handle search input with debounce
        let searchTimeout = null;
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (searchTerm.length > 0) {
                searchTimeout = setTimeout(() => {
                    // Redirect with search term
                    window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
                }, 1000); // Wait 1 second before redirecting
            } else {
                searchSuggestions.classList.add('d-none');
            }
        });

        // Handle suggestion clicks
        searchSuggestions.addEventListener('click', function(e) {
            const suggestion = e.target.closest('.suggestion');
            if (suggestion) {
                const searchTerm = suggestion.textContent.trim();
                searchInput.value = searchTerm;
                searchSuggestions.classList.add('d-none');
                window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
            }
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchInput') && !e.target.closest('#searchSuggestions')) {
                searchSuggestions.classList.add('d-none');
            }
        });

        // Handle form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            if (searchTerm !== '') {
                window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
            }
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
<?php require_once '../../includes/footer.php'; ?>