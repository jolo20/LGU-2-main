<?php require_once __DIR__ . '/auth.php'; 
 include 'includes/header.php';
?>
<div class="cardish">
  <h1>Dashboard</h1>
  <p>Welcome to your LGU Dashboard.</p>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="cardish text-center">
        <h4>Total Ordinances</h4>
        <p class="display-6">123</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cardish text-center">
        <h4>Upcoming Sessions</h4>
        <p class="display-6">5</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cardish text-center">
        <h4>Pending Requests</h4>
        <p class="display-6">8</p>
      </div>
    </div>
  </div>

  <hr>

  <h5>Recent Activities</h5>
  <ul>
    <li>Ordinance #2025-04 passed first reading</li>
    <li>Session scheduled for Aug 15, 2025</li>
    <li>Committee meeting set for Aug 20, 2025</li>
  </ul>
</div>
<?php include 'includes/footer.php'; ?>