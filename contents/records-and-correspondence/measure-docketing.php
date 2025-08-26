<?php
require_once '../../auth.php';
$pageTitle = "Measure Docketing";
require_once '../../includes/header.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "lgu2";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $searchCondition = " WHERE m.measure_title LIKE '%$searchTerm%' 
                        OR m.measure_content LIKE '%$searchTerm%'
                        OR m.docket_no LIKE '%$searchTerm%'
                        OR m.measure_type LIKE '%$searchTerm%'
                        OR m.introducers LIKE '%$searchTerm%'
                        OR m.MFL_Name LIKE '%$searchTerm%'";
}


$checkEmpty = $conn->query("SELECT COUNT(*) as count FROM m6_measuredocketing");
$row = $checkEmpty->fetch_assoc();

if ($row['count'] == 0) {
    $placeholderData = [
        [
            'MD001', // m6_MD_Code
            1, // m8_SC_ID
            101, // m8_SC_Code
            1, // m9_SC_ID
            201, // m9_SC_Code
            '2025-08-24', // date_created
            'resolution', // measure_type
            'Municipal Water System Improvement', // measure_title
            'A resolution authorizing the improvement and expansion of the municipal water system to provide better access to clean water for all barangays', // measure_content
            'Councilor Santos, Councilor Reyes', // introducers
            'under_review', // measure_status
            'For initial review', // checking_remarks
            'Please review technical specifications', // checking_notes
            'John Smith', // checked_by
            '2025-08-24 09:00:00', // datetime_submitted
            'DOCKET-2025-001', // docket_no
            'Technical Committee', // MFL_Name
            'Pending technical evaluation' // MFL_Feedback
        ],
        [
            'MD002',
            2,
            102,
            2,
            202,
            '2025-08-23',
            'ordinance',
            'Traffic Management Guidelines',
            'An ordinance establishing comprehensive traffic management guidelines to improve road safety and reduce congestion in the municipality',
            'Councilor Cruz, Councilor Lim',
            'under_review',
            'For committee review',
            'Review traffic impact assessment',
            'Maria Garcia',
            '2025-08-23 14:30:00',
            'DOCKET-2025-002',
            'Transportation Committee',
            'Awaiting committee inputs'
        ],
        [
            'MD003',
            3,
            103,
            3,
            203,
            '2025-08-22',
            'resolution',
            'Public Park Development Project',
            'A resolution approving the development of new public parks and recreational spaces to enhance community well-being',
            'Councilor Aquino',
            'under_review',
            'For environmental assessment',
            'Include environmental impact study',
            'Robert Tan',
            '2025-08-22 11:15:00',
            'DOCKET-2025-003',
            'Parks and Recreation Committee',
            'Environmental review ongoing'
        ]
    ];
    
    foreach ($placeholderData as $data) {
        $stmt = $conn->prepare("INSERT INTO m6_measuredocketing (
            m6_MD_Code, m8_SC_ID, m8_SC_Code, m9_SC_ID, m9_SC_Code,
            date_created, measure_type, measure_title, measure_content,
            introducers, measure_status, checking_remarks, checking_notes,
            checked_by, datetime_submitted, docket_no, MFL_Name, MFL_Feedback
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiisssssssssssss", 
            $data[0], $data[1], $data[2], $data[3], $data[4],
            $data[5], $data[6], $data[7], $data[8], $data[9],
            $data[10], $data[11], $data[12], $data[13],
            $data[14], $data[15], $data[16], $data[17]
        );
        $stmt->execute();
    }
}

// Pagination setup
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = 2;
$offset = ($page - 1) * $limit;

// Clean up the current URL parameters
$params = $_GET;
unset($params['page']); // Remove page from the base params

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM m6_measuredocketing m $searchCondition";
$totalResult = $conn->query($countQuery);
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'];
$totalPages = ceil($total / $limit);

// Main query with pagination
$sql = "SELECT m.m6_MD_ID, m.m6_MD_Code, m.measure_title, m.measure_content,
               m.date_created, m.measure_type, m.measure_status, m.docket_no,
               m.checking_remarks, m.checking_notes, m.checked_by,
               m.datetime_submitted, m.introducers, m.MFL_Name, m.MFL_Feedback
        FROM m6_measuredocketing m
        $searchCondition
        ORDER BY m.date_created DESC
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>

<!-- Start content wrapper -->
<div class="cardish">
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="ico">
            <i class="fa-solid fa-gavel fa-xl"></i>
        </div>
        <h1 class="mb-0">Measure Docketing</h1>
    </div>

    <!-- Search Form -->
    <div class="mb-4">
        <form method="GET" class="search-form" id="searchForm">
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       placeholder="Search measures..." 
                       name="search" 
                       value="<?= htmlspecialchars($searchTerm) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
  
    <table class="table table-bordered table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Date Filed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["measure_title"]); ?></td>
                        <td><?= htmlspecialchars($row["measure_content"]); ?></td>
                        <td><?= $row["date_created"]; ?></td>
                        <td>
                            <!-- Action Buttons -->
                            <div class="btn-group" role="group">
                                                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#measureModal<?= $row['m6_MD_ID'] ?>">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            
                            <!-- Combined View and Add Docket Modal -->
                            <div class="modal fade" id="measureModal<?= $row['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Measure Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Measure Details Column -->
                                                <div class="col-md-6">
                                                    <h6 class="border-bottom pb-2 mb-3">Measure Information</h6>
                                                    <p><strong>Measure Code:</strong> <?= htmlspecialchars($row["m6_MD_Code"]) ?></p>
                                                    <p><strong>Title:</strong> <?= htmlspecialchars($row["measure_title"]) ?></p>
                                                    <p><strong>Content:</strong> <?= htmlspecialchars($row["measure_content"]) ?></p>
                                                    <p><strong>Date Created:</strong> <?= $row["date_created"] ?></p>
                                                    <p><strong>Type:</strong> <?= ucfirst($row["measure_type"]) ?></p>
                                                    <p><strong>Status:</strong> <?= ucfirst($row["measure_status"]) ?></p>
                                                    <p><strong>Introducers:</strong> <?= htmlspecialchars($row["introducers"]) ?></p>
                                                    
                                                    <?php if ($row["docket_no"]): ?>
                                                        <h6 class="border-bottom pb-2 mb-3 mt-4">Current Docket Information</h6>
                                                        <p><strong>Docket Number:</strong> <?= htmlspecialchars($row["docket_no"]) ?></p>
                                                        <p><strong>Committee/Office:</strong> <?= htmlspecialchars($row["MFL_Name"]) ?></p>
                                                        <p><strong>Feedback:</strong> <?= htmlspecialchars($row["MFL_Feedback"]) ?></p>
                                                        <p><strong>Checking Remarks:</strong> <?= htmlspecialchars($row["checking_remarks"]) ?></p>
                                                        <p><strong>Checking Notes:</strong> <?= htmlspecialchars($row["checking_notes"]) ?></p>
                                                        <p><strong>Checked By:</strong> <?= htmlspecialchars($row["checked_by"]) ?></p>
                                                        <p><strong>Submitted:</strong> <?= $row["datetime_submitted"] ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Add Docket Form Column -->
                                                <div class="col-md-6">
                                                    <h6 class="border-bottom pb-2 mb-3">Add/Update Docket</h6>
                                                    <form action="add_docket.php" method="POST">
                                                        <input type="hidden" name="measure_id" value="<?= $row['m6_MD_ID'] ?>">
                                                        <div class="mb-3">
                                                            <label for="docket_number_<?= $row['m6_MD_ID'] ?>" class="form-label">Docket Number</label>
                                                            <input type="text" class="form-control" id="docket_number_<?= $row['m6_MD_ID'] ?>" name="docket_number" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="doc_type_<?= $row['m6_MD_ID'] ?>" class="form-label">Document Type</label>
                                                            <select class="form-select" id="doc_type_<?= $row['m6_MD_ID'] ?>" name="doc_type" required>
                                                                <option value="">Select Document Type</option>
                                                                <option value="ordinance">Ordinance</option>
                                                                <option value="resolution">Resolution</option>
                                                            </select>
                                                        </div>
                                                        <!-- Ordinance Categories -->
                                                        <div class="mb-3" id="categoryDiv_<?= $row['m6_MD_ID'] ?>" style="display: none;">
                                                            <label for="category_<?= $row['m6_MD_ID'] ?>" class="form-label">Category</label>
                                                            <select class="form-select" id="category_<?= $row['m6_MD_ID'] ?>" name="category">
                                                                <option value="">Select Category</option>
                                                                <option value="health">Health & Welfare</option>
                                                                <option value="education">Education</option>
                                                                <option value="infrastructure">Infrastructure & Development</option>
                                                                <option value="environment">Environment</option>
                                                                <option value="safety">Public Safety</option>
                                                                <option value="traffic">Transportation & Traffic</option>
                                                                <option value="recognition">Recognition & Commendation</option>
                                                                <option value="budget">Budget & Finance</option>
                                                                <option value="barangay">Barangay Affairs</option>
                                                                <option value="franchise">Franchise & Permits</option>
                                                            </select>
                                                        </div>
                                                        <!-- Resolution Subjects -->
                                                        <div class="mb-3" id="subjectDiv_<?= $row['m6_MD_ID'] ?>" style="display: none;">
                                                            <label for="subject_<?= $row['m6_MD_ID'] ?>" class="form-label">Subject</label>
                                                            <select class="form-select" id="subject_<?= $row['m6_MD_ID'] ?>" name="subject">
                                                                <option value="">Select Subject</option>
                                                                <option value="health">Health & Welfare</option>
                                                                <option value="education">Education</option>
                                                                <option value="infrastructure">Infrastructure & Development</option>
                                                                <option value="environment">Environment</option>
                                                                <option value="safety">Public Safety</option>
                                                                <option value="traffic">Transportation & Traffic</option>
                                                                <option value="recognition">Recognition & Commendation</option>
                                                                <option value="budget">Budget & Finance</option>
                                                                <option value="barangay">Barangay Affairs</option>
                                                                <option value="franchise">Franchise & Permits</option>
                                                            </select>
                                                        </div>
                                                        <script>
                                                        (function(){
                                                            function update(id){
                                                                const docType = document.getElementById('doc_type_'+id);
                                                                const categoryDiv = document.getElementById('categoryDiv_'+id);
                                                                const subjectDiv = document.getElementById('subjectDiv_'+id);
                                                                const categorySelect = document.getElementById('category_'+id);
                                                                const subjectSelect = document.getElementById('subject_'+id);
                                                                if(!docType) return;

                                                                if(docType.value === 'ordinance'){
                                                                    if(categoryDiv) categoryDiv.style.display = 'block';
                                                                    if(subjectDiv) subjectDiv.style.display = 'none';
                                                                    if(categorySelect) categorySelect.required = true;
                                                                    if(subjectSelect){ subjectSelect.required = false; subjectSelect.value = ''; }
                                                                } else if(docType.value === 'resolution'){
                                                                    if(categoryDiv) categoryDiv.style.display = 'none';
                                                                    if(subjectDiv) subjectDiv.style.display = 'block';
                                                                    if(categorySelect){ categorySelect.required = false; categorySelect.value = ''; }
                                                                    if(subjectSelect) subjectSelect.required = true;
                                                                } else {
                                                                    if(categoryDiv) categoryDiv.style.display = 'none';
                                                                    if(subjectDiv) subjectDiv.style.display = 'none';
                                                                    if(categorySelect){ categorySelect.required = false; categorySelect.value = ''; }
                                                                    if(subjectSelect){ subjectSelect.required = false; subjectSelect.value = ''; }
                                                                }
                                                            }

                                                            document.addEventListener('DOMContentLoaded', function(){
                                                                document.querySelectorAll('select[id^="doc_type_"]').forEach(function(select){
                                                                    const id = select.id.replace('doc_type_','');
                                                                    update(id);
                                                                    select.addEventListener('change', function(){ update(id); });
                                                                });

                                                                // Re-check when modal is shown (Bootstrap)
                                                                document.querySelectorAll('.modal').forEach(function(modal){
                                                                    modal.addEventListener('shown.bs.modal', function () {
                                                                        const select = modal.querySelector('select[id^="doc_type"]');
                                                                        if(select){ update(select.id.replace('doc_type','')); }
                                                                    });
                                                                });
                                                            });
                                                        })();
                                                        </script>
                                                        <div class="mb-3">
                                                            <label for="from_dept_<?= $row['m6_MD_ID'] ?>" class="form-label">From</label>
                                                            <input type="text" class="form-control" id="from_dept_<?= $row['m6_MD_ID'] ?>" value="Ordinance Section" readonly>
                                                            <input type="hidden" name="from_dept" value="ordinance_section">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="to_dept_<?= $row['m6_MD_ID'] ?>" class="form-label">To</label>
                                                            <select class="form-select" id="to_dept_<?= $row['m6_MD_ID'] ?>" name="to_dept" required>
                                                                <option value="">Select Department</option>
                                                                <option value="mayors_office">Committee Journal Section</option>
                                                                <option value="sangguniang_bayan">Archive Section</option>
                                                                <option value="budget_office">Agenda & Briefing Section</option>
                                                                <option value="planning_office">Minutes Section</option>
                                                                <option value="engineering">Ordinance & Resolution Section</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="remarks_<?= $row['m6_MD_ID'] ?>" class="form-label">Remarks</label>
                                                            <textarea class="form-control" id="remarks_<?= $row['m6_MD_ID'] ?>" name="remarks" rows="3"></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">Save Docket</button>
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
                <tr><td colspan="9" class="text-center">No records found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($totalPages > 1): ?>
    <!-- Pagination Controls -->
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= ($page - 1) ?>&<?= http_build_query($params) ?>" aria-label="Previous">
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
                        <a class="page-link" href="?page=<?= ($page + 1) ?>&<?= http_build_query($params) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
        <div class="text-center mt-2 text-muted small">
            Showing <?= ($offset + 1) ?>-<?= min($offset + $limit, $total) ?> of <?= $total ?> documents
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>