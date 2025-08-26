<?php 
require_once '../../auth.php';
$pageTitle = "Categorization & Classification";
require_once '../../includes/header.php'; 

?>
<div class="cardish">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Categorization & Classification</h2>
        <form class="d-flex" id="searchForm">
            <div class="input-group">
                <input type="text" class="form-control" id="searchInput" 
                       placeholder="Search records...">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-search"></i>
                    <span class="visually-hidden">Search</span>
                </button>
            </div>
        </form>
    </div>

    <div class="row">
        <!-- Left Column - Categories -->
        <div class="col-md-3">
            <div class="list-group mb-3">
                <a href="#" class="list-group-item list-group-item-action active">
                    All Documents
                    <span class="badge bg-secondary float-end">10</span>
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    Health - Public Health
                    <span class="badge bg-secondary float-end">5</span>
                    <span class="badge bg-info mx-1">Priority</span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        Last updated: Aug 01
                    </small>
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    Health - Medical Services
                    <span class="badge bg-secondary float-end">3</span>
                    <span class="badge bg-info mx-1">Urgent</span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        Last updated: Jul 28
                    </small>
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    Infrastructure - Transportation
                    <span class="badge bg-secondary float-end">2</span>
                    <span class="badge bg-info mx-1">High Priority</span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        Last updated: Jun 15
                    </small>
                </a>
            </div>

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
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Category</th>
                                <th>Classification</th>
                                <th>Date Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>DOC-2025-001</td>
                                <td>Public Health Initiative</td>
                                <td>Resolution</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>Health</td>
                                <td>Public Health</td>
                                <td>08/01/2025</td>
                            </tr>
                            <tr>
                                <td>DOC-2025-002</td>
                                <td>Medical Services Enhancement</td>
                                <td>Ordinance</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>Health</td>
                                <td>Medical Services</td>
                                <td>07/28/2025</td>
                            </tr>
                            <tr>
                                <td>DOC-2025-003</td>
                                <td>Transportation Development Plan</td>
                                <td>Resolution</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>Infrastructure</td>
                                <td>Transportation</td>
                                <td>06/15/2025</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column - Category Management -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header">Add Category</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Category Key</label>
                        <input id="newKey" class="form-control" placeholder="e.g. community_health">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Category Label</label>
                        <input id="newLabel" class="form-control" placeholder="e.g. Community Health">
                    </div>
                    <button id="addCatBtn" class="btn btn-success w-100">Add Category</button>
                </div>
            </div>

            <div class="card p-2">
                <h6>Existing Categories</h6>
                <ul id="clientCats" class="list-group small list-group-flush mt-2">
                    <li class="list-group-item">
                        Health - Public Health
                        <small class="text-muted">(health_public)</small>
                        <span class="badge bg-info">Priority</span>
                    </li>
                    <li class="list-group-item">
                        Health - Medical Services
                        <small class="text-muted">(health_medical)</small>
                        <span class="badge bg-info">Urgent</span>
                    </li>
                    <li class="list-group-item">
                        Infrastructure - Transportation
                        <small class="text-muted">(infra_transport)</small>
                        <span class="badge bg-info">High Priority</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>

</script>
<?php require_once '../../includes/footer.php'; ?>