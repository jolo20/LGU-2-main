<?php require_once '../../auth.php';
$pageTitle = "draft creation";
require_once '../../includes/header.php';?>
<div class="cardish">
  <h3 class="mb-3">Draft Creation & Editing</h3>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead>
        <tr>
          <th>Draft ID</th><th>Title</th><th>Author</th><th>Created</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>D-2025-001</td><td>Solid Waste Segregation</td><td>J. Dela Cruz</td><td>2025-01-10</td><td>Editing</td></tr>
        <tr><td>D-2025-014</td><td>Road Safety Enhancement</td><td>M. Santos</td><td>2025-03-02</td><td>For Review</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../../includes/footer.php' ?>