<?php
require_once '../../auth.php';
$pageTitle = "Categorization & Classification";
require_once '../../includes/header.php';

// Database connection
require_once 'connection.php';
// Pagination setup for both tables
$docPage = max(1, isset($_GET['docPage']) ? (int)$_GET['docPage'] : 1);
$catPage = max(1, isset($_GET['catPage']) ? (int)$_GET['catPage'] : 1);
$docLimit = 4;  // Changed to 2 to show pagination
$catLimit = 5;
$docOffset = ($docPage - 1) * $docLimit;
$catOffset = ($catPage - 1) * $catLimit;

// Clean up the current URL parameters
$params = $_GET;
unset($params['docPage'], $params['catPage']); // Remove page parameters but keep search term if it exists
if (empty($params['search'])) {
    unset($params['search']); // Remove empty search term from params
}

// Handle search
$searchCondition = "";
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($searchTerm)) {
    $searchTermEscaped = $conn->real_escape_string($searchTerm);
    $searchCondition = " WHERE (
        md.docket_no LIKE '%$searchTermEscaped%' OR 
        md.measure_title LIKE '%$searchTermEscaped%' OR 
        md.measure_type LIKE '%$searchTermEscaped%' OR 
        md.measure_status LIKE '%$searchTermEscaped%' OR 
        c.category_name LIKE '%$searchTermEscaped%' OR 
        cl.class_name LIKE '%$searchTermEscaped%'
    )";
}

// Get category count for pagination
$catCountQuery = "SELECT COUNT(DISTINCT CONCAT(c.category_name, cl.class_name)) as total 
                 FROM m6_category c
                 JOIN m6_categorymeasure cm ON c.category_id = cm.category_id
                 JOIN m6_measuredocketing_fromresearch md ON cm.measure_id = md.m6_MD_ID
                 LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
                 LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
                 WHERE md.docket_no IS NOT NULL AND md.docket_no != ''";

if (!empty($searchCondition)) {
    $catCountQuery .= " AND " . substr($searchCondition, 7); // Remove the "WHERE" from the search condition
}
$catTotalResult = $conn->query($catCountQuery);
$catTotalRow = $catTotalResult->fetch_assoc();
$catTotal = $catTotalRow['total'];
$catTotalPages = ceil($catTotal / $catLimit);

// Fetch categories with counts and latest updates
$categoryQuery = "SELECT 
    c.category_name,
    cl.class_name as classification_name,
    GROUP_CONCAT(DISTINCT t.tag_name) as tag_names,
    COUNT(DISTINCT md.m6_MD_ID) as doc_count,
    MAX(md.date_created) as last_updated
FROM m6_category c
JOIN m6_categorymeasure cm ON c.category_id = cm.category_id
JOIN m6_measuredocketing_fromresearch md ON cm.measure_id = md.m6_MD_ID
LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
LEFT JOIN m6_tagmeasure tm ON md.m6_MD_ID = tm.measure_id
LEFT JOIN m6_tags t ON tm.tag_id = t.tag_id
WHERE md.docket_no IS NOT NULL AND md.docket_no != ''";

if (!empty($searchCondition)) {
    $categoryQuery .= " AND " . substr($searchCondition, 7); // Remove the "WHERE" from the search condition
}

$categoryQuery .= " GROUP BY c.category_name, cl.class_name
ORDER BY last_updated DESC
LIMIT $catLimit OFFSET $catOffset";

$categoryResult = $conn->query($categoryQuery);
$categories = [];
$totalDocs = 0;

if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
        $totalDocs += $row['doc_count'];
    }
}

// Get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT md.m6_MD_ID) as total 
               FROM m6_measuredocketing_fromresearch md
               LEFT JOIN m6_categorymeasure cm ON md.m6_MD_ID = cm.measure_id
               LEFT JOIN m6_category c ON cm.category_id = c.category_id
               LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
               LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
               WHERE md.docket_no IS NOT NULL AND md.docket_no != ''" .
    $searchCondition;
$totalResult = $conn->query($countQuery);
$totalRow = $totalResult->fetch_assoc();
$total = (int)$totalRow['total'];
$totalPages = ceil($total / $docLimit);

// Debug total count
error_log("Total documents: " . $total);
error_log("Total pages: " . $totalPages);

// Get active tab from URL or default to 'ordinances'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'ordinances';
$typeFilter = $activeTab === 'ordinances' ? "AND md.measure_type LIKE '%ordinance%'" : "AND md.measure_type LIKE '%resolution%'";

// Fetch documents with their categories
$documentsQuery = "SELECT 
    md.docket_no,
    md.measure_title,
    md.measure_type,
    md.measure_status,
    md.date_created,
    GROUP_CONCAT(DISTINCT c.category_name) as category_name,
    GROUP_CONCAT(DISTINCT cl.class_name) as classification_name,
    GROUP_CONCAT(DISTINCT t.tag_name) as tag_names,
    MAX(c.category_id) as category_id,
    MAX(cl.class_id) as class_id
FROM m6_measuredocketing_fromresearch md
LEFT JOIN m6_categorymeasure cm ON md.m6_MD_ID = cm.measure_id
LEFT JOIN m6_category c ON cm.category_id = c.category_id
LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
LEFT JOIN m6_tagmeasure tm ON md.m6_MD_ID = tm.measure_id
LEFT JOIN m6_tags t ON tm.tag_id = t.tag_id
WHERE md.docket_no IS NOT NULL AND md.docket_no != '' $typeFilter";

if (!empty($searchCondition)) {
    $documentsQuery .= " AND " . substr($searchCondition, 7); // Remove the "WHERE" from the search condition
}

// Update count query with type filter
$countQuery = str_replace(
    "WHERE md.docket_no IS NOT NULL AND md.docket_no != ''",
    "WHERE md.docket_no IS NOT NULL AND md.docket_no != '' $typeFilter",
    $countQuery
);

$documentsQuery .= " GROUP BY md.m6_MD_ID
ORDER BY md.date_created DESC
LIMIT $docLimit OFFSET $docOffset";

$documentsResult = $conn->query($documentsQuery);
$documents = [];

if ($documentsResult) {
    while ($row = $documentsResult->fetch_assoc()) {
        $documents[] = $row;
    }
}

// Helper function to determine badge class based on status
function getStatusBadge($status)
{
    $status = strtolower($status);
    switch ($status) {
        case 'approved':
        case 'completed':
            return 'bg-success';
        case 'pending':
        case 'in progress':
            return 'bg-warning text-dark';
        case 'rejected':
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
<div class="cardish">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Categorization & Classification</h2>
        <form class="d-flex" method="GET" action="" id="searchForm">
            <div class="input-group" style="max-width: 300px;">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search records..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" id="searchInput">
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
    <style>
        .nav-tabs {
            border-bottom: 2px solid rgba(var(--brand-rgb), 0.2);
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            color: var(--text);
            border: 2px solid transparent;
            border-bottom: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: -2px;
        }

        .nav-tabs .nav-link:hover {
            color: var(--brand);
            border-color: transparent;
            background-color: rgba(var(--brand-rgb), 0.05);
        }

        .nav-tabs .nav-link.active {
            color: var(--brand);
            background-color: #fff;
            border-color: rgba(var(--brand-rgb), 0.2);
            border-bottom: 2px solid #fff;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--brand);
        }

        .nav-tabs .nav-link .badge {
            margin-left: 0.5rem;
            font-size: 0.75rem;
            padding: 0.25em 0.6em;
            vertical-align: middle;
        }

        table {
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        table th {
            background: var(--brand);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(var(--brand-rgb), 0.1);
        }

        tr:hover {
            background-color: rgba(var(--brand-rgb), 0.02);
        }

        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 6px;
        }
    </style>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <ul class="nav nav-tabs" id="docTabs">
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'ordinances' ? 'active' : '' ?>"
                    href="?tab=ordinances&<?= http_build_query(array_diff_key($params, ['tab' => ''])) ?>">
                    <i class="fas fa-gavel me-1"></i> Ordinances
                    <span class="badge bg-secondary">
                        <?= $total ?>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === 'resolutions' ? 'active' : '' ?>"
                    href="?tab=resolutions&<?= http_build_query(array_diff_key($params, ['tab' => ''])) ?>">
                    <i class="fas fa-scroll me-1"></i> Resolutions
                    <span class="badge bg-secondary">
                        <?= $total ?>
                    </span>
                </a>
            </li>
        </ul>

        <form class="d-flex gap-2" method="GET" action="" id="searchForm">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
            <div class="input-group">
                <input type="text"
                    class="form-control"
                    name="search"
                    placeholder="Search <?= $activeTab ?>..."
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    id="searchInput">
                <?php if (!empty($searchTerm)): ?>
                    <button type="button" class="btn btn-outline-secondary" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    <div class="tab-content">
        <div class="row">
            <div class="col-md-9">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0">
                            <?= $activeTab === 'ordinances' ? 'Proposed Ordinances' : 'Proposed Resolutions' ?>
                            <span class="badge bg-secondary ms-2"><?= $total ?></span>
                        </h5>
                        <button class="btn btn-primary btn-sm" onclick="addNew()">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Docket No.</th>
                                    <th>Title</th>
                                    <th>Committee</th>
                                    <th>Status</th>
                                    <th>Subject</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                No <?= $activeTab === 'ordinances' ? 'ordinances' : 'resolutions' ?> found.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td class="text-nowrap">
                                                <strong><?= htmlspecialchars($doc['docket_no']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= date('M d, Y', strtotime($doc['date_created'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($doc['measure_title']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($doc['category_name'] ?? 'Uncategorized') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= getStatusBadge($doc['measure_status']) ?>">
                                                    <?= ucfirst(htmlspecialchars($doc['measure_status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($doc['classification_name'])): ?>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($doc['classification_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        onclick="editDocument('<?= htmlspecialchars($doc['docket_no']) ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="../../files/<?= htmlspecialchars($doc['docket_no']) ?>"
                                                        class="btn btn-sm btn-outline-primary"
                                                        target="_blank">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteDocument('<?= htmlspecialchars($doc['docket_no']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total > $docLimit): ?>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing <?= ($docOffset + 1) ?>-<?= min($docOffset + $docLimit, $total) ?>
                                of <?= $total ?> documents
                            </div>
                            <nav aria-label="Document navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($docPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?docPage=<?= ($docPage - 1) ?>&<?= http_build_query(array_diff_key($params, ['docPage' => ''])) ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($i == $docPage) ? 'active' : '' ?>">
                                            <a class="page-link" href="?docPage=<?= $i ?>&<?= http_build_query(array_diff_key($params, ['docPage' => ''])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($docPage < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?docPage=<?= ($docPage + 1) ?>&<?= http_build_query(array_diff_key($params, ['docPage' => ''])) ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Filters</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center active">
                            All Categories
                            <span class="badge bg-white text-primary"><?= $totalDocs ?></span>
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <?= htmlspecialchars($category['category_name']) ?>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($category['classification_name'] ?? '-') ?>
                                    </div>
                                </div>
                                <span class="badge bg-secondary"><?= $category['doc_count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($catTotalPages > 1): ?>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-center">
                            <nav aria-label="Category navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($catPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?catPage=<?= ($catPage - 1) ?>&<?= http_build_query(array_diff_key($params, ['catPage' => ''])) ?>">
                                                <span>&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $catTotalPages; $i++): ?>
                                        <li class="page-item <?= ($i == $catPage) ? 'active' : '' ?>">
                                            <a class="page-link" href="?catPage=<?= $i ?>&<?= http_build_query(array_diff_key($params, ['catPage' => ''])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($catPage < $catTotalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?catPage=<?= ($catPage + 1) ?>&<?= http_build_query(array_diff_key($params, ['catPage' => ''])) ?>">
                                                <span>&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card mt-3 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">Quick Legend</h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <i class="fas fa-info-circle text-primary"></i>
                                Click a category to filter documents
                            </li>
                            <li>
                                <i class="fas fa-lightbulb text-warning"></i>
                                Use search for precise filtering
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 end-0 m-3" style="z-index: 5;">
    <div class="btn-group-vertical shadow">
        <button type="button" class="btn btn-primary rounded-circle p-3 mb-2" onclick="addNew()">
            <i class="fas fa-plus"></i>
            <span class="visually-hidden">Add New</span>
        </button>
        <button type="button" class="btn btn-info rounded-circle p-3" onclick="toggleFilters()">
            <i class="fas fa-filter"></i>
            <span class="visually-hidden">Toggle Filters</span>
        </button>
    </div>
</div>

<script>
    function toggleFilters() {
        const filtersCol = document.querySelector('.col-md-3');
        filtersCol.classList.toggle('d-none');
        document.querySelector('.col-md-9').classList.toggle('col-md-12');
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle clear search button
        const clearButton = document.getElementById('clearSearch');
        const searchInput = document.getElementById('searchInput');

        if (clearButton) {
            clearButton.addEventListener('click', function() {
                window.location.href = window.location.pathname;
            });
        }
    });

    function addNew() {
        // TODO: Implement add new document functionality
        alert('Add new document functionality will be implemented here');
    }

    function editDocument(docketNo) {
        // TODO: Implement edit document functionality
        alert('Edit document ' + docketNo + ' functionality will be implemented here');
    }

    function deleteDocument(docketNo) {
        if (confirm('Are you sure you want to delete document ' + docketNo + '?')) {
            // TODO: Implement delete document functionality
            alert('Delete document ' + docketNo + ' functionality will be implemented here');
        }
    }

    function updateFilters() {
        // Collect all active filters
        const params = new URLSearchParams(window.location.search);
        const activeFilters = {
            tab: params.get('tab') || 'ordinances',
            search: params.get('search') || '',
            category: params.get('category') || '',
            status: params.get('status') || ''
        };

        // Update the URL with the new filters
        const newParams = new URLSearchParams();
        Object.entries(activeFilters).forEach(([key, value]) => {
            if (value) newParams.append(key, value);
        });

        // Update URL without reloading the page
        window.history.pushState({}, '', `${window.location.pathname}?${newParams.toString()}`);

        // Refresh the content (you'll need to implement this part)
        loadFilteredContent(activeFilters);
    }

    function loadFilteredContent(filters) {
        // TODO: Implement AJAX content loading
        console.log('Loading content with filters:', filters);
    }
</script>
<?php require_once '../../includes/footer.php'; ?>