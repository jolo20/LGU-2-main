<?php
require_once '../../auth.php';
$pageTitle = "Document Tracking";
require_once '../../includes/header.php';

require_once 'connection.php';

// Default active tab
$activeTab = 'incoming';

// Pagination setup
$transitPage = 1;
$reviewPage = 1;
$processedPage = 1;
$itemsPerPage = 5;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
if ($search !== '') {
    $search = $conn->real_escape_string($search);
    $whereClause = "WHERE md.measure_title LIKE '%$search%' 
                    OR md.docket_no LIKE '%$search%'
                    OR md.measure_type LIKE '%$search%'
                    OR md.category LIKE '%$search%'
                    OR md.introducers LIKE '%$search%'";
}

// Clean up URL parameters for pagination links
$params = $_GET;
unset($params['transitPage'], $params['reviewPage'], $params['processedPage']);

$query = "SELECT md.m6_MD_ID, md.measure_title, md.measure_content,
               md.date_created, md.measure_type, md.measure_status,
               md.checking_remarks, md.checking_notes, md.checked_by,
               md.datetime_submitted, md.introducers, md.docket_no, 
               md.category, md.subject, md.MFL_Name, md.MFL_Feedback
    FROM m6_measuredocketing_fromresearch md 
    $whereClause
    ORDER BY md.date_created DESC";

$result = $conn->query($query);
$allRows = [];
$noResults = false;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allRows[] = $row;
    }
    if (empty($allRows) && !empty($search)) {
        $noResults = true;
    }
}

// Split results into three columns based on status/phase and apply pagination
$cols = [[], [], []];
$totalItems = [0, 0, 0];
$pages = [1, 1, 1];

foreach ($allRows as $row) {
    if (empty($row['docket_no'])) {
        // Under Review - no docket number
        $totalItems[1]++;
        if (($totalItems[1] > ($reviewPage - 1) * $itemsPerPage) &&
            ($totalItems[1] <= $reviewPage * $itemsPerPage)
        ) {
            $cols[1][] = $row;
        }
    } else {
        // Processed - has docket number
        $totalItems[2]++;
        if (($totalItems[2] > ($processedPage - 1) * $itemsPerPage) &&
            ($totalItems[2] <= $processedPage * $itemsPerPage)
        ) {
            $cols[2][] = $row;
        }
    }
}

// Calculate total pages for each column
$totalPages = [
    ceil($totalItems[0] / $itemsPerPage),
    ceil($totalItems[1] / $itemsPerPage),
    ceil($totalItems[2] / $itemsPerPage)
];

// Function to generate pagination links
function generatePaginationLinks($currentPage, $totalPages, $tabName)
{
    if ($totalPages <= 1) return '';

    $links = '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">';

    // Previous button
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $links .= sprintf(
        '<li class="page-item %s"><a class="page-link" href="?tab=%s&%sPage=%d" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>',
        $prevDisabled,
        $tabName,
        $tabName,
        $currentPage - 1
    );

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $links .= sprintf(
            '<li class="page-item %s"><a class="page-link" href="?tab=%s&%sPage=%d">%d</a></li>',
            $active,
            $tabName,
            $tabName,
            $i,
            $i
        );
    }

    // Next button
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $links .= sprintf(
        '<li class="page-item %s"><a class="page-link" href="?tab=%s&%sPage=%d" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>',
        $nextDisabled,
        $tabName,
        $tabName,
        $currentPage + 1
    );

    $links .= '</ul></nav>';
    return $links;
}

// Close the connection after getting data
$conn->close();
?>

<div class="cardish">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Document Tracking</h2>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3 ms-auto">
            <form class="d-flex justify-content-end" method="GET" id="searchForm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" class="form-control form-control-sm" name="search" id="searchInput"
                        placeholder="Search documents..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="button" class="btn-close" id="clearSearch" aria-label="Clear search"
                        style="display: <?= isset($_GET['search']) && $_GET['search'] !== '' ? 'block' : 'none' ?>;">
                    </button>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa-solid fa-search"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <style>
        .nav-tabs .nav-link {
            color: var(--text);
            border: 1px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: var(--brand);
            color: var(--brand);
            isolation: isolate;
        }

        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: var(--brand);
            border-color: var(--brand);
        }
    </style>
    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'incoming' ? 'active' : '' ?>" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming"
                type="button" role="tab" aria-controls="incoming" aria-selected="<?= $activeTab === 'incoming' ? 'true' : 'false' ?>">Incoming Tasks</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'review' ? 'active' : '' ?>" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button"
                role="tab" aria-controls="review" aria-selected="<?= $activeTab === 'review' ? 'true' : 'false' ?>">Under Review</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'processed' ? 'active' : '' ?>" id="processed-tab" data-bs-toggle="tab" data-bs-target="#processed"
                type="button" role="tab" aria-controls="processed" aria-selected="<?= $activeTab === 'processed' ? 'true' : 'false' ?>">Processed</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Incoming Tasks Tab -->
        <div class="tab-pane fade <?= $activeTab === 'incoming' ? 'show active' : '' ?>" id="incoming" role="tabpanel" aria-labelledby="incoming-tab">
            <div class="card mb-3">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:var(--brand)">
                    Incoming Tasks
                    <span class="badge bg-light text-dark"><?= count($cols[0]) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="text-white" style="background:var(--brand)">
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Introducers</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cols[0])): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        <?= !empty($search) ? 'No matching documents found for "' . htmlspecialchars($search) . '"' : 'No documents available' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cols[0] as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['docket_no']) ?></td>
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#docModal<?= $doc['m6_MD_ID'] ?>">
                                                <?= htmlspecialchars($doc['measure_title']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($doc['measure_type']) ?></td>
                                        <td><?= htmlspecialchars($doc['introducers']) ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y', strtotime($doc['date_sent']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php echo generatePaginationLinks($transitPage, $totalPages[0], 'transit'); ?>
        </div>

        <!-- Under Review Tab -->
        <div class="tab-pane fade <?= $activeTab === 'review' ? 'show active' : '' ?>" id="review" role="tabpanel" aria-labelledby="review-tab">
            <div class="card mb-3">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:var(--brand)">
                    Under Review
                    <span class="badge bg-light text-dark"><?= count($cols[1]) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="text-white" style="background:var(--brand)">
                            <tr>
                                <th>Status</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Introducers</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cols[1])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <?= !empty($search) ? 'No matching documents found for "' . htmlspecialchars($search) . '"' : 'No documents available' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cols[1] as $doc): ?>
                                    <tr class="<?= empty($doc['docket_no']) ? 'table-warning' : '' ?>">
                                        <td>
                                            <?php if (empty($doc['docket_no'])): ?>
                                                <span class="badge bg-warning text-dark">Pending Docket</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($doc['docket_no']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($doc['measure_title']) ?></td>
                                        <td><?= htmlspecialchars($doc['measure_type']) ?></td>
                                        <td><?= htmlspecialchars($doc['introducers']) ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y', strtotime($doc['date_created']))) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#docModal<?= $doc['m6_MD_ID'] ?>">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php echo generatePaginationLinks($reviewPage, $totalPages[1], 'review'); ?>
        </div>

        <!-- Processed Tab -->
        <div class="tab-pane fade <?= $activeTab === 'processed' ? 'show active' : '' ?>" id="processed" role="tabpanel" aria-labelledby="processed-tab">
            <div class="card mb-3">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:var(--brand)">
                    Processed
                    <span class="badge bg-light text-dark"><?= count($cols[2]) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="text-white" style="background:var(--brand)">
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>MFL Name</th>
                                <th>MFL Feedback</th>
                                <th>Introducers</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cols[2])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <?= !empty($search) ? 'No matching documents found for "' . htmlspecialchars($search) . '"' : 'No documents available' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cols[2] as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['docket_no']) ?></td>
                                        <td><?= htmlspecialchars($doc['measure_title']) ?></td>
                                        <td><?= htmlspecialchars($doc['measure_type']) ?></td>
                                        <td><?= htmlspecialchars((string)$doc['MFL_Name']) ?: '<span class="badge bg-warning text-dark">Pending</span>' ?></td>
                                        <td><?= htmlspecialchars((string)$doc['MFL_Feedback']) ?: '<span class="badge bg-secondary">No Feedback</span>' ?></td>
                                        <td><?= htmlspecialchars($doc['introducers']) ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y', strtotime($doc['date_created']))) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#docModal<?= $doc['m6_MD_ID'] ?>">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php echo generatePaginationLinks($processedPage, $totalPages[2], 'processed'); ?>
        </div>
    </div>

    <!-- Detail modals -->
    <?php foreach ($allRows as $doc): ?>
        <div class="modal fade" id="docModal<?= $doc['m6_MD_ID'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Document Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="border-bottom pb-2">Document Information</h6>
                                <p><strong>Docket No:</strong>
                                    <?php if (!empty($doc['docket_no'])): ?>
                                        <?= htmlspecialchars($doc['docket_no']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending Assignment</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Title:</strong> <?= htmlspecialchars($doc['measure_title']) ?></p>
                                <p><strong>Content:</strong>

                                <p style="text-align: center;"><?= nl2br(htmlspecialchars($doc["measure_content"], ENT_QUOTES, 'UTF-8')) ?></p>
                                </p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($doc['measure_type']) ?></p>
                                <?php if (strcasecmp($doc['measure_type'], 'Ordinance') === 0 && empty($doc['category'])): ?>
                                    <p><strong>Category:</strong> Not Specified</p>
                                <?php elseif (!empty($doc['category'])): ?>
                                    <p><strong>Category:</strong> <?= htmlspecialchars($doc['category']) ?></p>
                                <?php elseif (strcasecmp($doc['measure_type'], 'Resolution') === 0 && empty($doc['subject'])): ?>
                                    <p><strong>Category:</strong> Not Specified</p>
                                <?php elseif (!empty($doc['subject'])): ?>
                                    <p><strong>Category:</strong> <?= htmlspecialchars($doc['subject']) ?></p>
                                <?php endif; ?>
                                <p><strong>Recently Checked By:</strong> <?= htmlspecialchars($doc['checked_by']) ?></p>
                                <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($doc['measure_status'])) ?></p>
                                <p><strong>MFL Name:</strong> <?= ucfirst(htmlspecialchars((string)$doc['MFL_Name'])) ?: 'Pending' ?></p>
                                <p><strong>MFL Feedback:</strong> <?= ucfirst(htmlspecialchars((string)$doc['MFL_Feedback'])) ?: 'No Feedback' ?></p>
                                <p><strong>Created:</strong> <?= date("m/d/Y", strtotime($doc["date_created"])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    document.addEventListener('DOMContentLoaded', function() {
        // Handle clear search button

        const clearButton = document.getElementById('clearSearch');
        if (clearButton) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent button from keeping focus
                searchInput.value = '';
                const currentTab = document.querySelector('.nav-link.active').id.replace('-tab', '');
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.set('tab', currentTab);
                window.location.href = url.toString();
            });

            // Show/hide clear button based on input
            searchInput.addEventListener('input', function() {
                clearButton.style.display = this.value.trim() === '' ? 'none' : 'block';
            });

            // Remove focus from clear button after click
            clearButton.addEventListener('mouseup', function() {
                this.blur();
            });
        }

        // Remove focus from search button after click
        document.querySelector('#searchForm button[type="submit"]').addEventListener('mouseup', function() {
            this.blur();
        });

        // Update pagination links when there's a search query
        function updatePaginationLinks() {
            const searchQuery = document.getElementById('searchInput').value.trim();
            const currentTab = document.querySelector('.nav-link.active').id.replace('-tab', '');

            document.querySelectorAll('.pagination .page-link').forEach(link => {
                const url = new URL(link.href);
                if (searchQuery) {
                    url.searchParams.set('search', searchQuery);
                }
                url.searchParams.set('tab', currentTab);
                link.href = url.toString();
            });
        }

        // Handle search form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.trim();

            if (searchTerm === '') {
                return;
            }

            // Get current active tab
            const activeTab = document.querySelector('.nav-link.active').id.replace('-tab', '');

            // Build the URL with current tab and search term
            const url = new URL(window.location.href);
            url.searchParams.set('tab', activeTab);
            url.searchParams.set('search', searchTerm);

            // Redirect to the search URL
            window.location.href = url.toString();

            // If using client-side search, prevent form submission and perform search
            if (window.location.href.includes('search=')) {
                e.preventDefault();
                performSearch(searchTerm);
            }
        });

        // Handle tab changes using localStorage
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(tabLink => {
            tabLink.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.id.replace('-tab', '');
                // Save active tab to localStorage
                localStorage.setItem('activeDocumentTab', tabId);
            });
        });

        // Set active tab on page load based on localStorage
        const savedTab = localStorage.getItem('activeDocumentTab');
        if (savedTab) {
            const tabToActivate = document.querySelector(`#${savedTab}-tab`);
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }

        // Set initial state
        const url = new URL(window.location.href);
        const currentTab = document.querySelector('.nav-link.active').id.replace('-tab', '');
        if (!url.searchParams.has('tab')) {
            url.searchParams.set('tab', currentTab);
            window.history.replaceState({}, '', url.toString());
        }

        // Update pagination links on page load
        updatePaginationLinks();

        const searchInput = document.getElementById('searchInput');
        const tables = document.querySelectorAll('table');
        const allRows = Array.from(document.querySelectorAll('table tbody tr')).filter(row => !row.querySelector('td[colspan]'));
        const originalRows = [...allRows];

        function performSearch(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            let hasAnyResults = false;

            originalRows.forEach(row => {
                const text = Array.from(row.cells)
                    .map(cell => cell.textContent.toLowerCase())
                    .join(' ');

                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    hasAnyResults = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update empty state for each table
            tables.forEach(table => {
                const tbody = table.querySelector('tbody');
                const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.style.display !== 'none' && !row.classList.contains('no-results'));

                // Remove existing no results row if it exists
                const existingNoResults = tbody.querySelector('tr.no-results');
                if (existingNoResults) {
                    existingNoResults.remove();
                }

                // Get the number of columns for this table
                const colCount = table.querySelector('thead tr').children.length;

                // Add no results row if needed
                if (visibleRows.length === 0) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results';
                    const cell = document.createElement('td');
                    cell.colSpan = colCount;
                    cell.className = 'text-center text-muted py-3';
                    cell.innerHTML = searchTerm ? 'No matching documents found for "' + searchTerm + '"' : 'No documents available';
                    noResultsRow.appendChild(cell);
                    tbody.appendChild(noResultsRow);
                }
            });
        }

        // Handle real-time search
        searchInput.addEventListener('input', function() {
            performSearch(this.value);
        });

        // Handle form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault(); // Prevent empty submissions
            }
        });
    });
</script>
<?php require_once '../../includes/footer.php' ?>