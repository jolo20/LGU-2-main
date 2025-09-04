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
$baseQuery = "SELECT * FROM m6_measuredocketing_fromresearch";
$whereClause = '';
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $whereClause = " WHERE measure_title LIKE ? OR measure_content LIKE ? OR docket_no LIKE ?";
    $searchParam = "%$searchTerm%";
    $params = [$searchParam, $searchParam, $searchParam];
    $types = 'sss';
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

<div class="container-fluid mt-4">
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
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Progress</th>
                                        <th>Next Action</th>
                                        <th>Est. Completion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docs as $doc): ?>
                                        <tr>
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
                                            <td>
                                                <?php if (!empty($doc['ai_analysis']['next_actions'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($doc['ai_analysis']['next_actions'] as $action): ?>
                                                            <li><small>• <?= htmlspecialchars($action) ?></small></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">No pending actions</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                            </td>
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
                                            <th>Next Action</th>
                                            <th>Est. Completion</th>
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
                                                    <?php if ($activeTab === 'accomplished'): ?>
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
                                                    <td>
                                                        <?php if (!empty($doc['ai_analysis']['next_actions'])): ?>
                                                            <ul class="list-unstyled mb-0">
                                                                <?php foreach ($doc['ai_analysis']['next_actions'] as $action): ?>
                                                                    <li><small>• <?= htmlspecialchars($action) ?></small></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <span class="text-muted">No pending actions</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                                    </td>
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Document Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- AI Analysis Section -->
                        <div class="card mb-3 bg-light">
                            <div class="card-body">
                                <h6 class="card-title">AI Analysis</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Status:</strong>
                                            <span class="badge <?= getStatusBadgeClass($doc['ai_analysis']['status'], $doc['ai_analysis']['confidence']) ?>">
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
                                            <strong>Est. Completion:</strong>
                                            <?= date('M d, Y', strtotime($doc['ai_analysis']['estimated_completion'])) ?>
                                        </p>
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
                                <p><strong>Title:</strong> <?= htmlspecialchars($doc['measure_title']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($doc['measure_type']) ?></p>
                                <p><strong>Docket No:</strong>
                                    <?= !empty($doc['docket_no']) ? htmlspecialchars($doc['docket_no']) :
                                        '<span class="badge bg-warning text-dark">Pending</span>' ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date Created:</strong>
                                    <?= date('M d, Y', strtotime($doc['date_created'])) ?>
                                </p>
                                <p><strong>Status:</strong></break>
                                    <?= ucwords($doc['measure_status']) ?>
                                <div class="text-muted small">
                                    <?= date('M d, Y', strtotime($doc['date_created'])) ?>
                                </div>
                                </p>
                                <p><strong>Checked By:</strong>
                                    <?= !empty($doc['checked_by']) ? htmlspecialchars($doc['checked_by']) :
                                        '<span class="badge bg-secondary">Not yet reviewed</span>' ?>
                                </p>
                            </div>
                        </div>

                        <h6 class="mt-3">Content</h6>
                        <div class="border p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <?= nl2br(htmlspecialchars($doc['measure_content'])) ?>
                        </div>

                        <?php if (!empty($doc['checking_remarks'])): ?>
                            <h6 class="mt-3">Remarks</h6>
                            <p><?= nl2br(htmlspecialchars($doc['checking_remarks'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
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