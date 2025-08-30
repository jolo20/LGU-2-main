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
        cc.category_name LIKE '%$searchTermEscaped%' OR 
        cc.classification_name LIKE '%$searchTermEscaped%'
    )";
}

// Get category count for pagination
$catCountQuery = "SELECT COUNT(DISTINCT CONCAT(c.category_name, cl.class_name)) as total 
                 FROM m6_category c
                 JOIN m6_categorymeasure cm ON c.category_id = cm.category_id
                 JOIN m6_measuredocketing_fromresearch md ON cm.measure_id = md.m6_MD_ID
                 LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
                 LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
                 WHERE md.docket_no IS NOT NULL AND md.docket_no != ''" .
                 $searchCondition;
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
WHERE md.docket_no IS NOT NULL AND md.docket_no != ''" .
$searchCondition . "
GROUP BY c.category_name, cl.class_name
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

// Fetch documents with their categories
$documentsQuery = "SELECT 
    md.docket_no,
    md.measure_title,
    md.measure_type,
    md.measure_status,
    md.date_created,
    GROUP_CONCAT(DISTINCT c.category_name) as category_names,
    GROUP_CONCAT(DISTINCT cl.class_name) as classification_names,
    GROUP_CONCAT(DISTINCT t.tag_name) as tag_names
FROM m6_measuredocketing_fromresearch md
LEFT JOIN m6_categorymeasure cm ON md.m6_MD_ID = cm.measure_id
LEFT JOIN m6_category c ON cm.category_id = c.category_id
LEFT JOIN m6_classmeasure clm ON md.m6_MD_ID = clm.measure_id
LEFT JOIN m6_classifications cl ON clm.class_id = cl.class_id
LEFT JOIN m6_tagmeasure tm ON md.m6_MD_ID = tm.measure_id
LEFT JOIN m6_tags t ON tm.tag_id = t.tag_id
WHERE md.docket_no IS NOT NULL AND md.docket_no != ''" .
$searchCondition . "
GROUP BY md.m6_MD_ID
ORDER BY md.date_created DESC
LIMIT $docLimit OFFSET $docOffset";

$documentsResult = $conn->query($documentsQuery);
$documents = [];

if ($documentsResult) {
    while ($row = $documentsResult->fetch_assoc()) {
        $documents[] = $row;
    }
}

?>
<div class="cardish">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Categorization & Classification</h2>
        <form class="d-flex" method="GET" action="">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search records..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-search"></i> Search
                </button>
                <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                <a href="?" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="ordinances-tab" data-bs-toggle="tab" data-bs-target="#ordinances" type="button"
                role="tab" aria-controls="ordinances" aria-selected="false">Proposed Ordinances</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resolutions-tab" data-bs-toggle="tab" data-bs-target="#resolutions"
                type="button" role="tab" aria-controls="resolutions" aria-selected="false">Proposed Resolutions</button>
        </li>
    </ul>
    <div class="row">
        <!-- Left Column - Categories -->
        <div class="col-md-3">
            <div class="list-group mb-3">
                <a href="#" class="list-group-item list-group-item-action active">
                    All Documents
                    <span class="badge bg-secondary float-end">
                        <?= $totalDocs ?>
                    </span>
                </a>
                <?php foreach ($categories as $category): ?>
                <a href="#" class="list-group-item list-group-item-action">
                    <?= htmlspecialchars($category['category_name']) ?> -
                    <?= htmlspecialchars($category['classification_name']) ?>
                    <span class="badge bg-secondary float-end">
                        <?= $category['doc_count'] ?>
                    </span>
                    <span class="badge bg-info mx-1">
                        <?= htmlspecialchars($category['tag_name']) ?>
                    </span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        Last updated:
                        <?= date('M d', strtotime($category['last_updated'])) ?>
                    </small>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if ($catTotalPages > 1): ?>
            <div class="d-flex justify-content-center mt-2 mb-3">
                <nav aria-label="Category navigation">
                    <ul class="pagination pagination-sm">
                        <?php if ($catPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?catPage=<?= ($catPage - 1) ?>&docPage=<?= $docPage ?>&<?= http_build_query($params) ?>">
                                <span>&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $catTotalPages; $i++): ?>
                        <li class="page-item <?= ($i == $catPage) ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?catPage=<?= $i ?>&docPage=<?= $docPage ?>&<?= http_build_query($params) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($catPage < $catTotalPages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?catPage=<?= ($catPage + 1) ?>&docPage=<?= $docPage ?>&<?= http_build_query($params) ?>">
                                <span>&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <div class="card p-2">
                <h6 class="mb-2">Quick Legend</h6>
                <ul class="mb-0 small">
                    <li>Use the right panel to add categories (client-side only).</li>
                    <li>Click a category to filter documents.</li>
                </ul>
            </div>
        </div>

        <!-- Middle Column - Documents -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">Documents</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-bordered mb-0" style="width: 100%; table-layout: fixed;">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Category</th>
                                <th>Subject</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No documents found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($doc['docket_no']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($doc['measure_title']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($doc['measure_type']) ?>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-<?= $doc['measure_status'] === 'approved' ? 'success' : 
                                                                   ($doc['measure_status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst(htmlspecialchars($doc['measure_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($doc['category_name'] ?? 'Uncategorized') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($doc['classification_name'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= date('m/d/Y', strtotime($doc['date_created'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Controls -->
            <?php if ($total > $docLimit): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Document navigation">
                    <ul class="pagination">
                        <?php if ($docPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?docPage=<?= ($docPage - 1) ?>&catPage=<?= $catPage ?>&<?= http_build_query($params) ?>"
                                aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $docPage) ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?docPage=<?= $i ?>&catPage=<?= $catPage ?>&<?= http_build_query($params) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($docPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?docPage=<?= ($docPage + 1) ?>&catPage=<?= $catPage ?>&<?= http_build_query($params) ?>"
                                aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <!-- Always show results count -->
            <div class="text-center mt-2 text-muted small">
                <?php if (!empty($searchTerm)): ?>
                Found
                <?= $total ?> matching documents
                <?php else: ?>
                Showing
                <?= ($docOffset + 1) ?>-
                <?= min($docOffset + $docLimit, $total) ?> of
                <?= $total ?> documents
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
    // No JavaScript needed for basic search functionality
</script>
</script>
<?php require_once '../../includes/footer.php'; ?>
