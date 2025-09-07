<?php
require_once '../../auth.php';
$pageTitle = "Document Tracking";
require_once '../../includes/header.php';
require_once '../../connection.php';
require_once 'DocumentTracker.php';

// Initialize the Document Tracker
$tracker = new DocumentTracker($conn);

// Initialize search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get active tab from URL or default to 'under_review'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'under_review';

// Base query
$baseQuery = "SELECT md.m6_MD_ID,
            md.date_created, md.measure_title, md.measure_type,
            md.measure_content, md.date_enacted, md.checking_remarks,
            m.file_name, m.file_path, md.introducers,
            md.datetime_submitted, md.category, md.subject,
            COALESCE(m.measure_status, 'draft') as measure_status,
            m.docket_no, m.sp_no
            FROM m6_measuredocketing_fromresearch md
            LEFT JOIN m6_measures m on md.m6_MD_ID = m.measure_id";
$whereClause = '';
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $whereClause = " WHERE md.measure_title LIKE ? OR md.measure_content LIKE ? OR m.measure_status LIKE ? OR m.docket_no LIKE ? OR m.sp_no LIKE ?";
    $searchParam = "%$searchTerm%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    $types = 'sssss';
}

$query = $baseQuery . $whereClause;
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$documents = [];
while ($row = $result->fetch_assoc()) {
    // Analyze each document using the AI tracker
    $analysis = $tracker->analyzeDocument($row);
    $row['ai_analysis'] = $analysis;
    $documents[] = $row;
}

// Group documents by status
$groupedDocs = [
    'under_review' => [],
    'accomplished' => [],
    'enacted' => []
];

foreach ($documents as $doc) {
    $status = $doc['ai_analysis']['status'];
    $groupedDocs[$status][] = $doc;
}

function getStatusBadgeClass($status, $confidence)
{
    switch ($status) {
        case 'under_review':
            return $confidence > 0.7 ? 'bg-warning' : 'bg-warning text-dark';
        case 'accomplished':
            return $confidence > 0.7 ? 'bg-success' : 'bg-success text-dark';
        case 'enacted':
            return $confidence > 0.7 ? 'bg-primary' : 'bg-primary text-dark';
        default:
            return 'bg-secondary';
    }
}

?>
<style>
    .modal-dialog{
        width: 100%;
    max-width: 500px;
    }
    .modal-body {
        scrollbar-width: auto;
        scrollbar-color: var(--brand) #ffffff;
    }

    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #ffffff;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background-color: var(--brand);
        border-radius: 10px;
        border: 2px solid #ffffff;
    }
    
</style>
<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Document Tracking</h2>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex justify-content-end" action="document-tracking.php">
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" class="form-control" name="search"
                        placeholder="Search documents..."
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($searchTerm)): ?>
        <!-- Combined Search Results -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Search Results for: "<?= htmlspecialchars($searchTerm) ?>"</h5>
            </div>
            <div class="card-body">
                <?php foreach ($groupedDocs as $status => $docs): ?>
                    <?php if (!empty($docs)): ?>
                        <h5 class="border-bottom pb-2 mb-3 text-capitalize">
                            <strong><?= str_replace('_', ' ', $status) ?> Documents</strong>
                        </h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                    <?php if (!empty($docs[0]['sp_no'])): ?>
                                        <th>Sp No.</th>
                                    <?php endif; ?>
                                    <?php if (!empty($docs[0]['docket_no'])): ?>
                                        <th>Docket No.</th>
                                    <?php endif; ?>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Progress</th>
                                    <?php if (!empty($docs[0]['date_enacted'])): ?>
                                        <th>Date Enacted</th>
                                    <?php else: ?>
                                        <th>Next Action</th>
                                        <th>Est. Completion</th>
                                    <?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docs as $doc): ?>
                                        <tr>
                                            <?php if (!empty($doc['sp_no'])): ?>
                                                <td><?= htmlspecialchars($doc['sp_no']) ?></td>
                                            <?php endif; ?>
                                            <?php if (!empty($doc['docket_no'])): ?>
                                                <td><?= htmlspecialchars($doc['docket_no']) ?></td>
                                            <?php endif; ?>
                                            <td><?= htmlspecialchars($doc['measure_title']) ?></td>
                                            <td><?= htmlspecialchars($doc['measure_type']) ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: <?= $doc['ai_analysis']['progress'] ?>%"
                                                        aria-valuenow="<?= $doc['ai_analysis']['progress'] ?>"
                                                        aria-valuemin="0" aria-valuemax="100">
                                                        <?= number_format($doc['ai_analysis']['progress']) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <?php if (!empty($doc['date_enacted'])): ?>
                                                        <td><?= date('M d, Y', strtotime($doc['date_enacted'])) ?></td>
                                                    <?php else: ?>
                                                        <td>
                                                            <?php if (!empty($doc['ai_analysis']['next_actions'])): ?>
                                                                <ul class="list-unstyled mb-0">
                                                                    <?php foreach ($doc['ai_analysis']['next_actions'] as $action): ?>
                                                                        <li><small>● <?= htmlspecialchars($action) ?></small></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <span class="text-muted">No pending actions</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($doc['ai_analysis']['estimated_completion'])): ?>
                                                                <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#documentModal<?= $doc['m6_MD_ID'] ?>">
                                                    View Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (array_sum(array_map('count', $groupedDocs)) === 0): ?>
                    <div class="alert alert-info">
                        No documents found matching your search criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Tabbed Interface -->
        <ul class="nav nav-tabs mb-4" id="myTab">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'under_review' ? 'active' : '' ?>"
                    href="document-tracking.php?tab=under_review" data-tab="under_review">Under Review</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'accomplished' ? 'active' : '' ?>"
                    href="document-tracking.php?tab=accomplished" data-tab="accomplished">Accomplished</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'enacted' ? 'active' : '' ?>"
                    href="document-tracking.php?tab=enacted" data-tab="enacted">Enacted</a>
            </li>
        </ul>

        <div class="tab-content">
            <?php foreach ($groupedDocs as $status => $docs): ?>
                <div class="tab-pane fade <?= $activeTab === $status ? 'show active' : '' ?>"
                    id="<?= $status ?>">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-capitalize"><?= str_replace('_', ' ', $status) ?> Documents</h5>
                            <span class="badge bg-primary"><?= count($docs) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <?php if ($activeTab === 'enacted'): ?>
                                                <th>Sp No.</th>
                                            <?php endif; ?>
                                            <?php if ($activeTab === 'enacted' || $activeTab === 'accomplished'): ?>
                                                <th>Docket No.</th>
                                            <?php endif; ?>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Progress</th>
                                            <?php if (!empty($docs[0]['date_enacted'])): ?>
                                            <th>Date Enacted</th>
                                            <?php else: ?>
                                            <th>Next Action</th>
                                            <th>Est. Completion</th>
                                            <?php endif; ?>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($docs)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-3">
                                                    No documents found in this category
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($docs as $doc): ?>
                                                <tr>
                                                    <?php if ($activeTab === 'enacted'): ?>
                                                        <td><?= !empty($doc['sp_no']) ? htmlspecialchars($doc['sp_no']) :
                                                            '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                                    <?php endif; ?>
                                                    <?php if ($activeTab === 'accomplished' || $activeTab === 'enacted'): ?>
                                                        <td>
                                                            <?= !empty($doc['docket_no']) ? htmlspecialchars($doc['docket_no']) :
                                                                '<span class="badge bg-warning text-dark">Pending</span>' ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars($doc['measure_title']) ?></td>
                                                    <td><?= htmlspecialchars($doc['measure_type']) ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                style="width: <?= $doc['ai_analysis']['progress'] ?>%"
                                                                aria-valuenow="<?= $doc['ai_analysis']['progress'] ?>"
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                <?= number_format($doc['ai_analysis']['progress']) ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <?php if (!empty($doc['date_enacted'])): ?>
                                                        <td><?= date('M d, Y', strtotime($doc['date_enacted'])) ?></td>
                                                    <?php else: ?>
                                                        <td>
                                                            <?php if (!empty($doc['ai_analysis']['next_actions'])): ?>
                                                                <ul class="list-unstyled mb-0">
                                                                    <?php foreach ($doc['ai_analysis']['next_actions'] as $action): ?>
                                                                        <li><small>● <?= htmlspecialchars($action) ?></small></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <span class="text-muted">No pending actions</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($doc['ai_analysis']['estimated_completion'])): ?>
                                                                <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#documentModal<?= $doc['m6_MD_ID'] ?>">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Document Modals -->
    <?php foreach ($documents as $doc): ?>
        <div class="modal fade" id="documentModal<?= $doc['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Document Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="flex-grow-1 overflow-auto" style="overflow-x: hidden !important; max-height: 70vh; padding-right: 5px;">
                        <!-- AI Analysis Section -->
                        <div class="card mb-3 bg-light">
                            <div class="card-body">
                                <h6 class="card-title">AI Analysis</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Status:</strong>
                                            <span class="badge <?= getStatusBadgeClass($doc['ai_analysis']['status'], $doc['ai_analysis']) ?>">
                                                <?= ucwords(str_replace('_', ' ', $doc['ai_analysis']['status'])) ?>
                                            </span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Progress:</strong>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: <?= $doc['ai_analysis']['progress'] ?>%"
                                                aria-valuenow="<?= $doc['ai_analysis']['progress'] ?>"
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?= number_format($doc['ai_analysis']['progress']) ?>%
                                            </div>
                                        </div>
                                        </p>
                                    </div>
                                    <div class="col-md-6">

                                        <p class="mb-2">
                                            <?php if (!empty($doc['date_enacted'])): ?>
                                                <strong>Date Enacted:</strong>
                                                <?= date('M d, Y', strtotime($doc['date_enacted'])) ?>
                                                <?php else: ?>
                                            <strong>Est. Completion:</strong>
                                            <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (empty($doc['date_enacted']) || empty($doc['sp_no'])): ?>
                                        <p class="mb-2">
                                            <strong>Assigned To:</strong></br>
                                            <?php if (empty($doc['docket_no'])): ?>
                                            <span class="badge bg-danger me-1">Records and Correspondence Section</span>
                                            <?php endif; ?>
                                                <?php if (!empty($doc['ai_analysis']['assigned_to']) && !empty($doc['docket_no'])): ?>
                                                    <?php foreach ($doc['ai_analysis']['assigned_to'] as $dept): ?>
                                                        <span class="badge bg-success me-1">
                                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $dept))) ?>
                                                        </span></br>
                                                    <?php endforeach; ?>
                                                <?php elseif (empty($doc['ai_analysis']['assigned_to']) && !empty($doc['docket_no'])): ?>
                                                    <span class="badge bg-warning text-dark">Unassigned</span>
                                                <?php endif; ?>
                                                </br>
                                            <?php if (!empty($doc['ai_analysis']['assigned_to'])): ?>
                                            <button type="button" class="btn btn-sm btn-primary ms-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#assignDeptModal<?= $doc['m6_MD_ID'] ?>">
                                                <i class="fas fa-user-plus"></i> Update
                                            </button>
                                            <?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>

                                </div>

                                <?php if (!empty($doc['ai_analysis']['next_actions'])): ?>
                                    <div class="mt-3">
                                        <strong>Recommended Actions:</strong>
                                        <ul class="list-group list-group-flush mt-2">
                                            <?php foreach ($doc['ai_analysis']['next_actions'] as $action): ?>
                                                <li class="list-group-item bg-transparent">
                                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                                    <?= htmlspecialchars($action) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Document Details Section -->
                        <h6>Document Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Introducers:</strong></br><small><?= htmlspecialchars($doc['introducers']) ?></small></p>
                                <p><strong>Title:</strong> <?= htmlspecialchars($doc['measure_title']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($doc['measure_type']) ?></p>
                                
                            </div>
                            <div class="col-md-6">
                                <p><strong>Docket No:</strong>
                                    <?= !empty($doc['docket_no']) ? htmlspecialchars($doc['docket_no']) :
                                        '<span class="badge bg-warning text-dark">Pending</span>' ?>
                                </p>
                                <?php if (!empty($doc['sp_no'])): ?>
                                    <p><strong>SP No:</strong> <?= htmlspecialchars($doc['sp_no']) ?></p>
                                <?php endif; ?>                

                                <p><strong>Date Created:</strong>
                                    <?= date('M d, Y', strtotime($doc['date_created'])) ?>
                                </p>
                                
                                <p><strong>Status:</strong></br>
                                    <?= ucwords($doc['measure_status']) ?></br>
                                    <span class="text-muted small"><?= date('M d, Y', strtotime($doc['date_created'])) ?></span>
                                </p>
                            </div>
                        </div>

                        <h6 class="mt-3">Content</h6>
                        <?php if (!empty($doc['measure_content'])): ?>
                            <div class="border p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <?= nl2br(htmlspecialchars($doc['measure_content'])) ?>
                            </div>
                        <?php endif; ?>
                        </br>
                        <?php if (!empty($doc) && !empty($doc['file_name'])): ?>
                        <?php 
                            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION)); 

                            $fileUrl = "/LGU-2-MAIN/" . $doc['file_path'] . "/" . rawurlencode($doc['file_name']);

                        ?>
                            <div class="border p-3 bg-light" style="max-height: 2000px; overflow-y: auto;">
                                <?php if ($ext === 'pdf'): ?>
                                    <embed src="<?= $fileUrl ?>" type="application/pdf" width="100%" height="600px">

                                <?php elseif (in_array($ext, ['docx', 'xlsx', 'pptx'])): ?>
                                    <p class="text-muted">
                                        This file type (<?= strtoupper($ext) ?>) cannot be viewed directly.
                                    </p>
                                    <a href="<?= $fileUrl ?>" class="btn btn-primary" download>
                                        Download <?= strtoupper($ext) ?> File
                                    </a>
                                
                                <?php else: ?>
                                    <p class="text-muted">Unsupported file type.</p>
                                    <a href="<?= $fileUrl ?>" class="btn btn-secondary" download>
                                        Download File
                                    </a>
                                    
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="border p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <p class="text-muted">No document file available.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($doc['checking_remarks'])): ?>
                            <h6 class="mt-3">Remarks</h6>
                            <p><?= nl2br(htmlspecialchars($doc['checking_remarks'])) ?></p>
                        <?php endif; ?>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <?php if (empty($doc['docket_no'])): ?>
                            <a href="measure-docketing.php?search=<?= $doc['m6_MD_ID'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Assign Docket No.
                            </a>
                        <?php endif; ?>
                        <?php if ($doc['ai_analysis']['status'] === 'accomplished'): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#assignDeptModal<?= $doc['m6_MD_ID'] ?>"
                                data-bs-dismiss="modal">
                                <i class="fas fa-user-plus me-1"></i> Assign
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Department Assignment Modals -->
    <?php foreach ($documents as $doc): ?>
        <div class="modal fade" id="assignDeptModal<?= $doc['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign to Department/Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="update_assignment.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="measure_id" value="<?= $doc['m6_MD_ID'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Select Department/Section</label>
                                <div class="list-group">
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="ordinance_resolution_section">
                                        Ordinance & Resolution Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="minutes_section">
                                        Minutes Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="event_calendar_section">
                                        Event Calendar Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="committee_management">
                                        Committee Management System
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="journal_section">
                                        Journal Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="public_hearing">
                                        Public Hearing Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="archive_section">
                                        Reference and Archive Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="research_section">
                                        Legislative Research Section
                                    </label>
                                    <label class="list-group-item">
                                        <input class="form-check-input me-1" type="checkbox" name="department[]" value="public_consultation">
                                        Public Consultation Management
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="assignment_notes<?= $doc['m6_MD_ID'] ?>" class="form-label">Notes</label>
                                <textarea class="form-control" id="assignment_notes<?= $doc['m6_MD_ID'] ?>" 
                                    name="notes" rows="3" placeholder="Add any additional notes about this assignment"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Assign Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Single unified tab handling
        document.querySelectorAll('#myTab .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tab = this.getAttribute('data-tab');
                const searchParam = new URLSearchParams(window.location.search).get('search') || '';

                // Construct URL with tab and search parameters
                let url = new URL('document-tracking.php', window.location.origin + window.location.pathname.replace('document-tracking.php', ''));
                url.searchParams.set('tab', tab);
                if (searchParam) {
                    url.searchParams.set('search', searchParam);
                }

                // Update active states immediately
                document.querySelectorAll('#myTab .nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });

                this.classList.add('active');
                const tabPane = document.getElementById(tab);
                if (tabPane) {
                    tabPane.classList.add('show', 'active');
                }

                // Update URL
                window.location.href = url.toString();
            });
        });

        // Fix search form
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    const searchTerm = searchInput.value;
                    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'under_review';

                    const url = new URL('document-tracking.php', window.location.origin + window.location.pathname.replace('document-tracking.php', ''));
                    url.searchParams.set('tab', currentTab);
                    url.searchParams.set('search', searchTerm);
                    window.location.href = url.toString();
                }
            });
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>