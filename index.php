<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Local Government Unit 2</title>

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
  <!-- Header -->
  <header class="header">
    <button class="burger" id="burger" aria-label="Open menu">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div class="title">LOCAL GOVERNMENT UNIT 2</div>

    <div class="profile dropdown">
      <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="avatar-32">
          <img src="assets/img/default-avatar.jpg" alt="User">
        </div>
        <i class="fa-solid fa-caret-down"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item profile-link" href="#" data-href="profile.php"><i class="fa-regular fa-user me-2"></i>Profile</a></li>
        <li><a class="dropdown-item settings-link" href="#" data-href="settings.php"><i class="fa-solid fa-gear me-2"></i>Settings</a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>

  <!-- Mobile backdrop -->
  <div id="backdrop" class="backdrop" aria-hidden="true"></div>

  <!-- Main layout -->
  <div class="wrapper" id="wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-top">
        <div class="profile-pod">
          <div class="text-center w-100">
            <div class="avatar-64 mx-auto mb-3">
              <img src="assets/img/default-avatar.jpg" alt="User">
            </div>
            <div class="user-name"><?= ucfirst($_SESSION['username'] ?? 'Guest') ?></div>
          </div>
        </div>
      </div>

      <nav class="side-nav" id="sideNav">

        <!-- Dashboard  -->
        <div class="nav-group open">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-gauge"></i></span>
            Dashboard
          </button>
          <ul class="sublist">
            <li><a href="#" data-href="dashboard.php" class="active">Overview</a></li>
          </ul>
        </div>
        <!-- 1 Ordinance & Resolution Tracking -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-gavel"></i></span>
            Ordinance and Resolution
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#" data-href="contents/ordinance-resolution-tracking/draft-creation.php">Draft Creation &
                Editing</a></li>
            <li><a href="#" data-href="contents/ordinance-resolution-tracking/sponsorship-management.php">Sponsorship &
                Author Management</a></li>
          </ul>
        </div>

        <!-- 2 Session & Meeting Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-handshake-angle"></i></span>
            Minutes Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#" data-href="contents/session-meeting-management/session-scheduling.php">Session Scheduling
                and Notifications</a></li>
            <li><a href="#" data-href="contents/session-meeting-management/agenda-builder.php">Agenda Builder</a></li>
          </ul>
        </div>

        <!-- 3 Legislative Agenda & Calendar (placeholder) -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-calendar-days"></i></span>
            Agenda and Briefing
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/legislative-agenda-calendar/placeholder.php?t=Event%20Calendar%20(Sessions,%20Hearings,%20Consultations)">Event
                Calendar (Sessions, Hearings, Consultations)</a></li>
            <li><a href="#"
                data-href="contents/legislative-agenda-calendar/placeholder.php?t=Priority%20Legislative%20List">Priority
                Legislative List</a></li>
          </ul>
        </div>

        <!-- 4 Committee Management System (placeholder) -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-users"></i></span>
            Committee Management System
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/committee-management-system/placeholder.php?t=Committee%20Creation%20%26%20Membership">Committee
                Creation & Membership</a></li>
            <li><a href="#"
                data-href="contents/committee-management-system/placeholder.php?t=Assignment%20of%20Legislative%20Items">Assignment
                of Legislative Items</a></li>
          </ul>
        </div>

        <!-- 5 Voting & Decision-Making -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-check-to-slot"></i></span>
            Committee Journal
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/voting-decision-making-system/placeholder.php?t=Roll%20Call%20Management">Roll Call
                Management</a></li>
            <li><a href="#"
                data-href="contents/voting-decision-making-system/placeholder.php?t=Motion%20Creation%20%26%20Seconding">Motion
                Creation & Seconding</a></li>
          </ul>
        </div>

        <!-- 6 Legislative Records Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-folder-open"></i></span>
            Records And Correspondence
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#" data-href="contents/records-and-correspondence/measure-docketing.php">Measure Docketing</a></li>
            <li><a href="#" data-href="contents/records-and-correspondence/categorization-and-classification.php">Categorization and Classification</a></li>
            <li><a href="#" data-href="contents/records-and-correspondence/document-tracking.php">Document Tracking</a></li>
          </ul>
        </div>

        <!-- 7 Public Hearing Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-microphone-lines"></i></span>
            Committee Hearing
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#" data-href="contents/public-hearing-management/placeholder.php?t=Hearing%20Schedule">Hearing
                Schedule</a></li>
            <li><a href="#"
                data-href="contents/public-hearing-management/placeholder.php?t=Speaker/Participant%20Registration">Speaker/Participant
                Registration</a></li>
          </ul>
        </div>

        <!-- 8 Legislative Archives -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-box-archive"></i></span>
            Archive Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/legislative-archives/placeholder.php?t=Enacted%20Ordinances%20Archive">Enacted
                Ordinances Archive</a></li>
          </ul>
        </div>

        <!-- 9 Legislative Research & Analysis -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
            Research Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/legislative-research-analysis/placeholder.php?t=Legislative%20Trends%20Dashboard">Legislative
                Trends Dashboard</a></li>
            <li><a href="#"
                data-href="contents/legislative-research-analysis/placeholder.php?t=Keyword%20%26%20Topic%20Search">Keyword
                & Topic Search</a></li>
          </ul>
        </div>

        <!-- 10 Public Consultation Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-comments"></i></span>
            Public Consultation Management
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="#"
                data-href="contents/public-consultation-management/placeholder.php?t=Public%20Feedback%20Portal">Public
                Feedback Portal</a></li>
            <li><a href="#"
                data-href="contents/public-consultation-management/placeholder.php?t=Survey%20Builder">Survey
                Builder</a></li>
            <li><a href="#" data-href="contents/public-consultation-management/placeholder.php?t=Issue%20Mapping">Issue
                Mapping</a></li>
          </ul>
        </div>
      </nav>

      <div class="sidebar-bottom"></div>
    </aside>

    <!-- Content -->
    <main class="content" id="content">
      <div class="cardish">
        <h1>Welcome</h1>
        <p>Select any submodule from the sidebar to load its content here.</p>
      </div>
    </main>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="logoutConfirmLabel">Confirm logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="mb-0">Are you sure you want to log out?</p>
        </div>

        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmLogoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Footer -->
  <footer class="footer">© <span id="year"></span>&nbsp;Local Government Unit 2 — All rights reserved</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/script.js"></script>
  <script>
    document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
      window.location.href = 'logout.php';
    });
  </script>
</body>

</html>