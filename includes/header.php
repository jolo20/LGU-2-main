<?php require_once __DIR__ . '/../auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <?php 
  // Calculate relative path to root
  $root = str_repeat('../', substr_count(trim($_SERVER['PHP_SELF'], '/'), '/') - 1);
  ?>
  <link href="<?= $root ?>assets/css/style.css" rel="stylesheet">
  <link href="<?= $root ?>assets/css/modal-fix.css" rel="stylesheet">
  <title><?= $pageTitle ?? 'Local Government Unit 2' ?></title>
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
          <img src="<?= $root ?>assets/img/default-avatar.jpg" alt="User">
        </div>
        <i class="fa-solid fa-caret-down"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item profile-link nav-link" href="<?= $root ?>profile.php"><i class="fa-regular fa-user me-2"></i>Profile</a></li>
        <li><a class="dropdown-item settings-link nav-link" href="<?= $root ?>settings.php"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
        <li><hr class="dropdown-divider"></li>
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
              <img src="<?= $root ?>assets/img/default-avatar.jpg" alt="User">
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
            <li><a href="<?= $root ?>dashboard.php" class="nav-link">Overview</a></li>
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
            <li><a href="<?= $root ?>contents/ordinance-resolution-tracking/draft-creation.php" class="nav-link">Draft Creation &
                Editing</a></li>
            <li><a href="<?= $root ?>contents/ordinance-resolution-tracking/sponsorship-management.php" class="nav-link">Sponsorship &
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
            <li><a href="<?= $root ?>contents/session-meeting-management/session-scheduling.php" class="nav-link">Session Scheduling
                and Notifications</a></li>
            <li><a href="contents/session-meeting-management/agenda-builder.php" class="nav-link">Agenda Builder</a></li>
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
            <li><a href="<?= $root ?>contents/legislative-agenda-calendar/placeholder.php?t=Event%20Calendar%20(Sessions,%20Hearings,%20Consultations)"
                class="nav-link">Event Calendar (Sessions, Hearings, Consultations)</a></li>
            <li><a href="<?= $root ?>contents/legislative-agenda-calendar/placeholder.php?t=Priority%20Legislative%20List"
                class="nav-link">Priority Legislative List</a></li>
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
            <li><a href="<?= $root ?>contents/committee-management-system/placeholder.php?t=Committee%20Creation%20%26%20Membership"
                class="nav-link">Committee Creation & Membership</a></li>
            <li><a href="contents/committee-management-system/placeholder.php?t=Assignment%20of%20Legislative%20Items"
                class="nav-link">Assignment of Legislative Items</a></li>
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
            <li><a href="contents/voting-decision-making-system/placeholder.php?t=Roll%20Call%20Management"
                class="nav-link">Roll Call Management</a></li>
            <li><a href="contents/voting-decision-making-system/placeholder.php?t=Motion%20Creation%20%26%20Seconding"
                class="nav-link">Motion Creation & Seconding</a></li>
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
            <li><a href="<?= $root ?>contents/records-and-correspondence/measure-docketing.php" class="nav-link">Measure Docketing</a></li>
            <li><a href="<?= $root ?>contents/records-and-correspondence/categorization-and-classification.php" class="nav-link">Categorization and Classification</a></li>
            <li><a href="<?= $root ?>contents/records-and-correspondence/document-tracking.php" class="nav-link">Document Tracking</a></li>
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
            <li><a href="<?= $root ?>contents/public-hearing-management/placeholder.php?t=Hearing%20Schedule" class="nav-link">Hearing
                Schedule</a></li>
            <li><a href="contents/public-hearing-management/placeholder.php?t=Speaker/Participant%20Registration" class="nav-link">Speaker/Participant
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
            <li><a href="<?= $root ?>contents/legislative-archives/placeholder.php?t=Enacted%20Ordinances%20Archive"
                class="nav-link">Enacted Ordinances Archive</a></li>
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
            <li><a href="<?= $root ?>" class="nav-link">Legislative
                Trends Dashboard</a></li>
            <li><a href="<?= $root ?>contents/legislative-research-section/KeywordandTopicSearch.php"class="nav-link">Keyword
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
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Public%20Feedback%20Portal"
                class="nav-link">Public Feedback Portal</a></li>
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Survey%20Builder"
                class="nav-link">Survey Builder</a></li>
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Issue%20Mapping"
                class="nav-link">Issue Mapping</a></li>
          </ul>
        </div>
      </nav>

      <div class="sidebar-bottom"></div>
    </aside>

    <!-- Content -->
    <main class="content" id="content">
