<?php
require_once '../../auth.php';
$pageTitle = "Categorization & Classification";
require_once '../../includes/header.php';
require_once '../../connection.php';
require_once 'AIClassifier.php';

// Initialize the AI Classifier
$classifier = new AIClassifier($conn);

// Pagination settings
$limit = 10; // Items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Check for feedback messages
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($_SESSION['message']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['message']);
}

// Initialize search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterCategory = isset($_GET['category']) ? $_GET['category'] : '';
$filterSubject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Base SQL for Ordinances and Resolutions with search condition
$searchCondition = '';
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $searchCondition = " WHERE (measure_title LIKE ? OR measure_content LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
    $types .= 'ss';
}

// Add filter conditions
if (!empty($filterType)) {
    $searchCondition .= empty($searchCondition) ? " WHERE" : " AND";
    $searchCondition .= " measure_type = ?";
    $params[] = $filterType;
    $types .= 's';

    if ($filterType === 'ordinance' && !empty($filterCategory) && $filterCategory !== 'all') {
        $searchCondition .= " AND category = ?";
        $params[] = $filterCategory;
        $types .= 's';
    } elseif ($filterType === 'resolution' && !empty($filterSubject) && $filterSubject !== 'all') {
        $searchCondition .= " AND subjects = ?";
        $params[] = $filterSubject;
        $types .= 's';
    }
}

// Function to classify document content using basic NLP rules
function classifyDocument($content, $title)
{
    $content = strtolower($content . ' ' . $title);

    // Define classification rules
    $categories = [
        'general welfare' => ['welfare', 'community', 'public', 'social'],
        'administrative' => ['administration', 'policy', 'procedure', 'management'],
        'health and sanitation' => ['health', 'medical', 'sanitation', 'hospital', 'clinic'],
        'education' => ['education', 'school', 'learning', 'student', 'academic'],
        'taxation' => ['tax', 'revenue', 'fee', 'payment', 'collection'],
        'infrastructure' => ['infrastructure', 'construction', 'building', 'road', 'bridge'],
        'environment' => ['environment', 'ecology', 'green', 'conservation', 'waste'],
        'peace and order' => ['peace', 'order', 'security', 'police', 'safety']
    ];

    $subjects = [
        'commendatory' => ['commend', 'recognition', 'award', 'acknowledge'],
        'congratulatory' => ['congratulate', 'achievement', 'success'],
        'authorizing' => ['authorize', 'permission', 'allow', 'grant'],
        'approving' => ['approve', 'approval', 'accept'],
        'request' => ['request', 'asking', 'seek'],
        'appeal' => ['appeal', 'petition', 'plea'],
        'administrative' => ['administrative', 'manage', 'organize']
    ];

    // Classify category
    $maxScore = 0;
    $bestCategory = 'general welfare'; // default category

    foreach ($categories as $category => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($content, $keyword);
        }
        if ($score > $maxScore) {
            $maxScore = $score;
            $bestCategory = $category;
        }
    }

    // Classify subject
    $maxScore = 0;
    $bestSubject = 'administrative'; // default subject

    foreach ($subjects as $subject => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($content, $keyword);
        }
        if ($score > $maxScore) {
            $maxScore = $score;
            $bestSubject = $subject;
        }
    }

    return ['category' => $bestCategory, 'subject' => $bestSubject];
}

?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <!-- Ordinance Categories Filter -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Ordinance Categories</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="all">All</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="general welfare">General Welfare</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="administrative">Administrative</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="health and sanitation">Health & Sanitation</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="education">Education</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="taxation">Taxation</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="infrastructure">Infrastructure</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="environment">Environment</button>
                        <button class="btn btn-outline-primary btn-sm filter-btn" data-type="ordinance" data-category="peace and order">Peace & Order</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <!-- Resolution Subjects Filter -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Resolution Subjects</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="all">All</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="commendatory">Commendatory</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="congratulatory">Congratulatory</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="authorizing">Authorizing</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="approving">Approving</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="request">Request</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="appeal">Appeal</button>
                        <button class="btn btn-outline-info btn-sm filter-btn" data-type="resolution" data-subject="administrative">Administrative</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="d-flex">
                        <input type="text" class="form-control me-2" placeholder="Search documents..."
                            name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($searchTerm)): ?>
        <!-- Combined Search Results -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Search Results for: "<?= htmlspecialchars($searchTerm) ?>"</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch Ordinances
                        $ordinanceStmt = $conn->prepare("SELECT *, 'ordinance' as doc_type FROM m6_measuredocketing_fromresearch 
                                                   $searchCondition AND measure_type = 'ordinance'");
                        if (!empty($searchTerm)) {
                            $ordinanceStmt->bind_param('ss', $searchParam, $searchParam);
                        }
                        $ordinanceStmt->execute();
                        $ordinanceResult = $ordinanceStmt->get_result();                        // Fetch Resolutions
                        $resolutionStmt = $conn->prepare("SELECT *, 'resolution' as doc_type FROM m6_measuredocketing_fromresearch 
                                                    $searchCondition AND measure_type = 'resolution'");
                        if (!empty($searchTerm)) {
                            $resolutionStmt->bind_param('ss', $searchParam, $searchParam);
                        }
                        $resolutionStmt->execute();
                        $resolutionResult = $resolutionStmt->get_result();

                        // Display Ordinances
                        if ($ordinanceResult->num_rows > 0): ?>
                            <h6 class="border-bottom pb-2 mb-3">Ordinances</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Docket No.</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $ordinanceResult->fetch_assoc()):
                                            $classification = classifyDocument($row['measure_content'], $row['measure_title']);
                                        ?>
                                            <tr>
                                                <td><?= !empty($row['docket_no']) ? htmlspecialchars($row['docket_no']) :
                                                        '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                                <td><?= htmlspecialchars($row['measure_title']) ?></td>
                                                <td><?= ucwords($classification['category']) ?></td>
                                                <td><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#documentModal<?= $row['m6_MD_ID'] ?>">
                                                        View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Display Resolutions -->
                        <?php if ($resolutionResult->num_rows > 0): ?>
                            <h6 class="border-bottom pb-2 mb-3">Resolutions</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Docket No.</th>
                                            <th>Title</th>
                                            <th>Subject</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $resolutionResult->fetch_assoc()):
                                            $classification = classifyDocument($row['measure_content'], $row['measure_title']);
                                        ?>
                                            <tr>
                                                <td><?= !empty($row['docket_no']) ? htmlspecialchars($row['docket_no']) :
                                                        '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                                <td><?= htmlspecialchars($row['measure_title']) ?></td>
                                                <td><?= ucwords($classification['subject']) ?></td>
                                                <td><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#documentModal<?= $row['m6_MD_ID'] ?>">
                                                        View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($ordinanceResult->num_rows === 0 && $resolutionResult->num_rows === 0): ?>
                            <div class="alert alert-info">No documents found matching your search criteria.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Tabbed Interface for Normal View -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#ordinances">Ordinances</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#resolutions">Resolutions</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Ordinances Tab -->
                            <div class="tab-pane fade show active" id="ordinances">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Docket No.</th>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Date Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Count total ordinances
                                            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM m6_measuredocketing_fromresearch WHERE measure_type = 'ordinance'");
                                            $countStmt->execute();
                                            $totalRow = $countStmt->get_result()->fetch_assoc();
                                            $totalOrdinances = $totalRow['total'];
                                            $totalPages = ceil($totalOrdinances / $limit);

                                            // Get paginated ordinances
                                            $stmt = $conn->prepare("SELECT * FROM m6_measuredocketing_fromresearch 
                                             WHERE measure_type = 'ordinance' 
                                             ORDER BY date_created DESC 
                                             LIMIT ? OFFSET ?");
                                            $stmt->bind_param('ii', $limit, $offset);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                                $classification = classifyDocument($row['measure_content'], $row['measure_title']);
                                            ?>
                                                <tr>
                                                    <td><?= !empty($row['docket_no']) ? htmlspecialchars($row['docket_no']) :
                                                            '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                                    <td><?= htmlspecialchars($row['measure_title']) ?></td>
                                                    <td><?= ucwords($classification['category']) ?></td>
                                                    <td><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#documentModal<?= $row['m6_MD_ID'] ?>">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>

                                    <!-- Pagination Controls -->
                                    <?php if ($totalPages > 1): ?>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="text-muted small">
                                                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalOrdinances) ?>
                                                of <?= $totalOrdinances ?> ordinances
                                            </div>
                                            <nav aria-label="Page navigation">
                                                <ul class="pagination pagination-sm mb-0">
                                                    <?php if ($page > 1): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?page=<?= ($page - 1) ?>" aria-label="Previous">
                                                                <span aria-hidden="true">&laquo;</span>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>

                                                    <?php
                                                    $startPage = max(1, $page - 2);
                                                    $endPage = min($totalPages, $page + 2);

                                                    if ($startPage > 1): ?>
                                                        <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                                        <?php if ($startPage > 2): ?>
                                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                                        </li>
                                                    <?php endfor; ?>

                                                    <?php if ($endPage < $totalPages): ?>
                                                        <?php if ($endPage < $totalPages - 1): ?>
                                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                                        <?php endif; ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                                        </li>
                                                    <?php endif; ?>

                                                    <?php if ($page < $totalPages): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?page=<?= ($page + 1) ?>" aria-label="Next">
                                                                <span aria-hidden="true">&raquo;</span>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div> <!-- Resolutions Tab -->
                            <div class="tab-pane fade" id="resolutions">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Docket No.</th>
                                                <th>Title</th>
                                                <th>Subject</th>
                                                <th>Date Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Count total resolutions
                                            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM m6_measuredocketing_fromresearch WHERE measure_type = 'resolution'");
                                            $countStmt->execute();
                                            $totalRow = $countStmt->get_result()->fetch_assoc();
                                            $totalResolutions = $totalRow['total'];
                                            $totalPages = ceil($totalResolutions / $limit);

                                            // Get paginated resolutions
                                            $stmt = $conn->prepare("SELECT * FROM m6_measuredocketing_fromresearch 
                                                             WHERE measure_type = 'resolution' 
                                                             ORDER BY date_created DESC 
                                                             LIMIT ? OFFSET ?");
                                            $stmt->bind_param('ii', $limit, $offset);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()):
                                                $classification = classifyDocument($row['measure_content'], $row['measure_title']);
                                            ?>
                                                <tr>
                                                    <td><?= !empty($row['docket_no']) ? htmlspecialchars($row['docket_no']) :
                                                            '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                                    <td><?= htmlspecialchars($row['measure_title']) ?></td>
                                                    <td><?= ucwords($classification['subject']) ?></td>
                                                    <td><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#documentModal<?= $row['m6_MD_ID'] ?>">
                                                            View Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Document Modal -->
    <?php
    // Reset result sets
    $stmt = $conn->prepare("SELECT * FROM m6_measuredocketing_fromresearch");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()):
        $classification = classifyDocument($row['measure_content'], $row['measure_title']);
    ?>
        <div class="modal fade" id="documentModal<?= $row['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Document Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Title</h6>
                                <p><?= htmlspecialchars($row['measure_title']) ?></p>

                                <h6>Content</h6>
                                <div class="border p-3 mb-3" style="max-height: 300px; overflow-y: auto;">
                                    <?= nl2br(htmlspecialchars($row['measure_content'])) ?>
                                </div>

                                <?php if ($row['measure_type'] === 'ordinance'): ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">AI Classification Results</h6>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2">Primary Category</h6>
                                            <p class="card-text">
                                                <?= ucwords($classification['category']) ?>
                                                <span class="badge bg-info">
                                                    <?= number_format($classification['confidence'] * 100, 1) ?>% confidence
                                                </span>
                                            </p>

                                            <h6 class="card-subtitle mb-2 mt-3">Alternative Categories</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                $alternatives = $classification['alternatives'];
                                                array_shift($alternatives); // Remove the primary category
                                                foreach (array_slice($alternatives, 0, 3) as $category => $confidence): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= ucwords($category) ?>
                                                        <span class="badge bg-secondary">
                                                            <?= number_format($confidence * 100, 1) ?>%
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <h6>Subject Classification</h6>
                                    <p><?= ucwords($classification['subject']) ?></p>
                                <?php endif; ?>

                                <h6>Date Created</h6>
                                <p><?= date("F d, Y", strtotime($row['date_created'])) ?></p>

                                <h6>Status</h6>
                                <p><?= ucwords($row['measure_status']) ?></p>

                                <?php if (!empty($row['checking_remarks'])): ?>
                                    <h6>Remarks</h6>
                                    <p><?= htmlspecialchars($row['checking_remarks']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <?php if ($row['measure_type'] === 'ordinance'): ?>
                            <form class="me-auto d-flex align-items-center" method="POST" action="update_category.php">
                                <input type="hidden" name="document_id" value="<?= $row['m6_MD_ID'] ?>">
                                <label class="me-2 text-muted small">Correct category:</label>
                                <select class="form-select form-select-sm me-2" name="correct_category" style="width: auto;">
                                    <option value="">Choose...</option>
                                    <option value="general welfare">General Welfare</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="health and sanitation">Health and Sanitation</option>
                                    <option value="education">Education</option>
                                    <option value="taxation">Taxation</option>
                                    <option value="infrastructure">Infrastructure</option>
                                    <option value="environment">Environment</option>
                                    <option value="peace and order">Peace and Order</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle filter button clicks
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const type = this.dataset.type;
                const category = this.dataset.category;
                const subject = this.dataset.subject;

                // Get current URL and parameters
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);

                // Update parameters
                params.set('type', type);
                if (type === 'ordinance') {
                    params.set('category', category);
                    params.delete('subject');
                } else {
                    params.set('subject', subject);
                    params.delete('category');
                }

                // Remove 'all' filters
                if (category === 'all') params.delete('category');
                if (subject === 'all') params.delete('subject');

                // Reset to page 1 when filtering
                params.set('page', '1');

                // Update URL and reload page
                window.location.href = `${url.pathname}?${params.toString()}`;
            });
        });

        // Highlight active filters
        const params = new URLSearchParams(window.location.search);
        const currentType = params.get('type');
        const currentCategory = params.get('category');
        const currentSubject = params.get('subject');

        filterButtons.forEach(button => {
            const buttonType = button.dataset.type;
            const buttonCategory = button.dataset.category;
            const buttonSubject = button.dataset.subject;

            if (buttonType === currentType) {
                if (buttonType === 'ordinance' && buttonCategory === currentCategory) {
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                } else if (buttonType === 'resolution' && buttonSubject === currentSubject) {
                    button.classList.remove('btn-outline-info');
                    button.classList.add('btn-info');
                } else if (buttonCategory === 'all' || buttonSubject === 'all') {
                    if (!currentCategory && !currentSubject) {
                        button.classList.remove(buttonType === 'ordinance' ? 'btn-outline-primary' : 'btn-outline-info');
                        button.classList.add(buttonType === 'ordinance' ? 'btn-primary' : 'btn-info');
                    }
                }
            }
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>