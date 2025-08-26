<?php
require_once '../../auth.php';
$pageTitle = "Document Tracking";
require_once '../../includes/header.php';

$conn = new mysqli("localhost", "root", "", "lgu2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
if ($search !== '') {
    $search = $conn->real_escape_string($search);
    $whereClause = "WHERE md.measure_title LIKE '%$search%' 
                    OR md.m6_MD_Code LIKE '%$search%'
                    OR dt.remarks LIKE '%$search%'";
}

$query = "SELECT 
    dt.tracking_id,
    dt.measure_id,
    dt.remarks,
    dt.date_sent,
    dt.date_received,
    md.m6_MD_Code,
    md.measure_title,
    md.measure_type,
    md.measure_status,
    md.date_created
    FROM m6_document_tracking dt
    JOIN m6_measuredocketing md ON dt.measure_id = md.m6_MD_ID
    $whereClause
    ORDER BY dt.date_sent DESC";

$result = $conn->query($query);
$allRows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allRows[] = $row;
    }
}

// Split results into three columns based on status/phase
$cols = [[], [], []];
foreach ($allRows as $row) {
    if (empty($row['date_received'])) {
        // Still in transit - first column
        $cols[0][] = $row;
    } else if ($row['measure_status'] === 'pending') {
        // In review - second column
        $cols[1][] = $row;
    } else {
        // Processed - third column
        $cols[2][] = $row;
    }
}

// Close the connection after getting data
$conn->close();
?>

<div class="cardish">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Document Tracking</h2>
    </div>

    <div class="row">
        <div class="col-12 mb-3">
            <form class="d-flex" method="GET" action="/LGU-2-MAIN/index.php">
  <div class="input-group">
    <input type="hidden" name="src" value="<?= htmlspecialchars($_GET['src'] ?? 'contents/records-and-correspondence/document-tracking.php') ?>">
    <input type="text" class="form-control" name="search" placeholder="Search records..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
    <button class="btn btn-primary" type="submit">Search</button>
  </div>
</form>

        </div>
    </div>

    <div class="row">
        <?php foreach (['In Transit', 'Under Review', 'Processed'] as $c => $title): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($title) ?>
                        <span class="badge bg-light text-dark"><?= count($cols[$c]) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Docket No.</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cols[$c])): ?>
                                    <tr><td colspan="3" class="text-center">No documents</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cols[$c] as $doc): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($doc['m6_MD_Code']) ?></td>
                                            <td>
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#docModal<?= $doc['tracking_id'] ?>">
                                                    <?= htmlspecialchars($doc['measure_title']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($doc['date_sent']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Detail modals -->
    <?php foreach ($allRows as $doc): ?>
        <div class="modal fade" id="docModal<?= $doc['tracking_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Document Tracking Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2">Document Information</h6>
                                <p><strong>Document Code:</strong> <?= htmlspecialchars($doc['m6_MD_Code']) ?></p>
                                <p><strong>Title:</strong> <?= htmlspecialchars($doc['measure_title']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($doc['measure_type']) ?></p>
                                <p><strong>Status:</strong> <?= htmlspecialchars($doc['measure_status']) ?></p>
                                <p><strong>Created:</strong> <?= htmlspecialchars($doc['date_created']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2">Tracking Information</h6>
                                <p><strong>Date Sent:</strong> <?= htmlspecialchars($doc['date_sent']) ?></p>
                                <p><strong>Date Received:</strong> <?= htmlspecialchars($doc['date_received'] ?? 'Pending') ?></p>
                                <p><strong>Remarks:</strong> <?= htmlspecialchars($doc['remarks']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once '../../includes/footer.php' ?>