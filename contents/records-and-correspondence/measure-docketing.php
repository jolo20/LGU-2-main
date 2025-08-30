<?php
require_once '../../auth.php';
$pageTitle = "Measure Docketing";
require_once '../../includes/header.php';

require_once 'connection.php';


$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = ' WHERE (m.docket_no IS NULL OR m.docket_no = "")';
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $searchCondition .= " AND (m.measure_title LIKE '%$searchTerm%' 
                        OR m.measure_content LIKE '%$searchTerm%'
                        OR m.measure_type LIKE '%$searchTerm%'
                        OR m.measure_status LIKE '%$searchTerm%'
                        or m.checking_remarks LIKE '%$searchTerm%'
                        OR m.introducers LIKE '%$searchTerm%'
                        OR m.date_created LIKE '%$searchTerm%')";
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
            <div class="input-group" style="max-width: 300px;">
                <input type="text" class="form-control form-control-sm" placeholder="Search measures..." name="search"
                    id="searchInput" value="<?= htmlspecialchars($searchTerm) ?>">
                <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSearch">
                    <i class="fa-solid fa-times"></i>
                    <span class="visually-hidden">Clear</span>
                </button>
                <?php endif; ?>
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="fa-solid fa-search"></i>
                    <span class="visually-hidden">Search</span>
                </button>
            </div>
        </form>
    </div>
    <div class="card mb-3">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:var(--brand)">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
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
                        <td>
                            <!-- Action Buttons -->
                            <div class="btn-group" role="group">
                                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#measureModal<?= $row['m6_MD_ID'] ?>">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>

                            <!-- Combined View and Add Docket Modal -->
                            <div class="modal fade" id="measureModal<?= $row['m6_MD_ID'] ?>" tabindex="-1"
                                aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Measure Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <style>
                                                    .measure-details {
                                                        height: 600px;
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
                                                    <p><strong>Measure Code:</strong>
                                                        <?= htmlspecialchars($row["m6_MD_Code"]) ?>
                                                    </p>
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
                                                        </< /p>
                                                    <p><strong>Type:</strong>
                                                        <?= ucfirst($row["measure_type"]) ?>
                                                    </p>
                                                    <p><strong>Status:</strong>
                                                        <?= ucfirst($row["measure_status"]) ?>
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
                                                </div>

                                                <!-- Add Docket Form Column -->
                                                <div class="col-md-6">
                                                    <h6 class="border-bottom pb-2 mb-3">Add/Update Docket</h6>
                                                    <form action="add_docket.php" method="POST">
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
                                                            <input type="text" class="form-control"
                                                                id="doc_type_<?= $row['m6_MD_ID'] ?>" name="doc_type"
                                                                value="<?= ucfirst($row[" measure_type"]) ?>" readonly>
                                                            <!-- Hidden input to pass the real value -->
                                                            <input type="hidden" name="doc_type"
                                                                value="<?= strtolower($row[" measure_type"]) ?>">

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
                                                                <option value="health">Health & Welfare</option>
                                                                <option value="education">Education</option>
                                                                <option value="infrastructure">Infrastructure &
                                                                    Development</option>
                                                                <option value="environment">Environment</option>
                                                                <option value="safety">Public Safety</option>
                                                                <option value="traffic">Transportation & Traffic
                                                                </option>
                                                                <option value="recognition">Recognition & Commendation
                                                                </option>
                                                                <option value="budget">Budget & Finance</option>
                                                                <option value="barangay">Barangay Affairs</option>
                                                                <option value="franchise">Franchise & Permits</option>
                                                            </select>
                                                        </div>
                                                        <!-- Resolution Subjects -->
                                                        <div class="mb-3" id="subjectDiv_<?= $row['m6_MD_ID'] ?>"
                                                            style="display: none;">
                                                            <label for="subject_<?= $row['m6_MD_ID'] ?>"
                                                                class="form-label">Subject</label>
                                                            <select class="form-select"
                                                                id="subject_<?= $row['m6_MD_ID'] ?>" name="subjects">
                                                                <option value="">Select Subject</option>
                                                                <option value="health">Health & Welfare</option>
                                                                <option value="education">Education</option>
                                                                <option value="infrastructure">Infrastructure &
                                                                    Development</option>
                                                                <option value="environment">Environment</option>
                                                                <option value="safety">Public Safety</option>
                                                                <option value="traffic">Transportation & Traffic
                                                                </option>
                                                                <option value="recognition">Recognition & Commendation
                                                                </option>
                                                                <option value="budget">Budget & Finance</option>
                                                                <option value="barangay">Barangay Affairs</option>
                                                                <option value="franchise">Franchise & Permits</option>
                                                            </select>
                                                        </div>
                                                        <script>
                                                            (function () {
                                                                function update(id) {
                                                                    const docType = document.getElementById('doc_type_' + id);
                                                                    const categoryDiv = document.getElementById('categoryDiv_' + id);
                                                                    const subjectDiv = document.getElementById('subjectDiv_' + id);
                                                                    const categorySelect = document.getElementById('category_' + id);
                                                                    const subjectSelect = document.getElementById('subject_' + id);
                                                                    if (!docType) return;

                                                                    // Convert to lowercase for case-insensitive comparison
                                                                    const docTypeValue = docType.value.toLowerCase();

                                                                    if (docTypeValue.includes('ordinance')) {
                                                                        if (categoryDiv) categoryDiv.style.display = 'block';
                                                                        if (subjectDiv) subjectDiv.style.display = 'none';
                                                                        if (categorySelect) categorySelect.required = true;
                                                                        if (subjectSelect) { subjectSelect.required = false; subjectSelect.value = ''; }
                                                                    } else if (docTypeValue.includes('resolution')) {
                                                                        if (categoryDiv) categoryDiv.style.display = 'none';
                                                                        if (subjectDiv) subjectDiv.style.display = 'block';
                                                                        if (categorySelect) { categorySelect.required = false; categorySelect.value = ''; }
                                                                        if (subjectSelect) subjectSelect.required = true;
                                                                    } else {
                                                                        if (categoryDiv) categoryDiv.style.display = 'none';
                                                                        if (subjectDiv) subjectDiv.style.display = 'none';
                                                                        if (categorySelect) { categorySelect.required = false; categorySelect.value = ''; }
                                                                        if (subjectSelect) { subjectSelect.required = false; subjectSelect.value = ''; }
                                                                    }
                                                                }

                                                                document.addEventListener('DOMContentLoaded', function () {
                                                                    // Initial update for all document type inputs
                                                                    document.querySelectorAll('input[id^="doc_type_"]').forEach(function (input) {
                                                                        const id = input.id.replace('doc_type_', '');
                                                                        update(id);
                                                                    });

                                                                    // Re-check when modal is shown (Bootstrap)
                                                                    document.querySelectorAll('.modal').forEach(function (modal) {
                                                                        modal.addEventListener('shown.bs.modal', function () {
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
                                                                value="<?= $row['checked_by'] ?>" readonly>
                                                            <input type="hidden" name="from_dept"
                                                                value="<?= $row['checked_by'] ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="to_dept_<?= $row['m6_MD_ID'] ?>"
                                                                class="form-label">To</label>
                                                            <select class="form-select"
                                                                id="to_dept_<?= $row['m6_MD_ID'] ?>" name="to_dept"
                                                                required>
                                                                <option value="">Select Department</option>
                                                                <option value="mayors_office">Committee Journal Section
                                                                </option>
                                                                <option value="sangguniang_bayan">Archive Section
                                                                </option>
                                                                <option value="budget_office">Agenda & Briefing Section
                                                                </option>
                                                                <option value="planning_office">Minutes Section</option>
                                                                <option value="engineering">Ordinance & Resolution
                                                                    Section</option>
                                                            </select>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Handle clear search button
        const clearButton = document.getElementById('clearSearch');
        const searchInput = document.getElementById('searchInput');

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                window.location.href = window.location.pathname;
            });
        }
        const tableBody = document.querySelector('table tbody');
        const originalRows = Array.from(tableBody.getElementsByTagName('tr')).filter(row => !row.querySelector('td[colspan]'));

        function performSearch(searchTerm) {
            searchTerm = searchTerm.toLowerCase();

            originalRows.forEach(row => {
                const text = Array.from(row.cells)
                    .map(cell => cell.textContent.toLowerCase())
                    .join(' ');

                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Check if we need to show "No results" message
            const visibleRows = originalRows.filter(row => row.style.display !== 'none');

            // Remove existing no results message if it exists
            const existingNoResults = tableBody.querySelector('tr.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // Add no results message if needed
            if (visibleRows.length === 0) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                const cell = document.createElement('td');
                cell.colSpan = 8; // Adjust based on number of columns
                cell.className = 'text-center';
                cell.textContent = 'No matching measures found';
                noResultsRow.appendChild(cell);
                tableBody.appendChild(noResultsRow);
            }
        }

        // Handle real-time search
        searchInput.addEventListener('input', function () {
            performSearch(this.value);
        });

        // Handle form submission
        document.getElementById('searchForm').addEventListener('submit', function (e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault(); // Prevent empty submissions
            }
        });
    });
</script>
<?php require_once '../../includes/footer.php'; ?>