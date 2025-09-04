document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const burger = document.getElementById('burger');
  const backdrop = document.getElementById('backdrop');
  const content = document.getElementById('content');
  const groups = document.querySelectorAll('.nav-group');

  // Mobile push sidebar
  const openMobile = () => body.classList.add('mobile-open');
  const closeMobile = () => body.classList.remove('mobile-open');
  const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

  if (burger) burger.addEventListener('click', () => {
    body.classList.toggle('mobile-open');
  });
  if (backdrop) backdrop.addEventListener('click', closeMobile);
  window.addEventListener('resize', () => { if (!isMobile()) closeMobile(); });

  
  // Handle settings and profile links normally - no special handling needed

  // Expand/collapse groups + remember state
  const storageKeyGroup = 'lgu_open_groups';
  const openGroups = new Set(JSON.parse(localStorage.getItem(storageKeyGroup) || '[]'));
// No special handling for dashboard since it's a direct link now

groups.forEach((g, idx) => {
  const btn = g.querySelector('.group-toggle');
  const key = `g${idx}`;
  if (openGroups.has(key)) g.classList.add('open');

  btn.addEventListener('click', () => {
    // Close all other groups except the clicked group
    groups.forEach((otherG, otherIdx) => {
      if (otherG !== g) {
        otherG.classList.remove('open');
        openGroups.delete(`g${otherIdx}`);
      }
    });

    // Toggle the clicked group
    g.classList.toggle('open');

    // Save open group to localStorage
    if (g.classList.contains('open')) {
      openGroups.add(key);
    } else {
      openGroups.delete(key);
    }

    localStorage.setItem(storageKeyGroup, JSON.stringify([...openGroups]));
  });
});



});

// Logout confirmation handling
(function () {
  // find all logout links: add class "logout-link" to your logout anchors for clarity; selector supports both
  const logoutSelectors = 'a.logout-link, a[data-action="logout"], .dropdown-menu a[href*="logout"], .dropdown-menu a.logout';
  const logoutLinks = Array.from(document.querySelectorAll(logoutSelectors));

  // fallback: if none found by fancy selector, try the profile dropdown anchor text "Logout" (robust-ish)
  if (logoutLinks.length === 0) {
    const anchors = Array.from(document.querySelectorAll('.dropdown-menu a'));
    anchors.forEach(a => {
      if (/\blogout\b/i.test(a.textContent || '')) logoutLinks.push(a);
    });
  }

  // modal elements
  const logoutModalEl = document.getElementById('logoutConfirmModal');
  if (!logoutModalEl) return; // modal not added yet
  const logoutModal = new bootstrap.Modal(logoutModalEl, { backdrop: 'static', keyboard: true });
  const confirmBtn = document.getElementById('confirmLogoutBtn');

  // store the URL to navigate to when confirmed
  let pendingLogoutUrl = null;

  // helper to close any open dropdowns (so modal is visible and dropdown doesn't remain open)
  function closeOpenDropdowns() {
    document.querySelectorAll('.dropdown.show').forEach(drop => {
      drop.classList.remove('show');
      const menu = drop.querySelector('.dropdown-menu');
      if (menu) menu.classList.remove('show');
      const toggler = drop.querySelector('[data-bs-toggle="dropdown"]');
      if (toggler) toggler.setAttribute('aria-expanded', 'false');
    });
  }

  // attach listeners
  logoutLinks.forEach(link => {
    link.addEventListener('click', (ev) => {
      ev.preventDefault();

      // compute the target URL (data-href favored, then href)
      const dataHref = link.getAttribute('data-href');
      const href = link.getAttribute('href');
      if (dataHref && dataHref.trim() && dataHref.trim() !== '#') pendingLogoutUrl = dataHref.trim();
      else if (href && href.trim() && href.trim() !== '#') pendingLogoutUrl = href.trim();
      else pendingLogoutUrl = 'LGU-2-MAIN/login.php'; // fallback

      // if you want to show a message that includes the user name, you can set it here

      // close dropdowns to avoid z-index oddities and then show modal
      closeOpenDropdowns();
      logoutModal.show();
    });
  });

  // when confirm clicked — perform logout navigation
  confirmBtn.addEventListener('click', () => {
    logoutModal.hide();
    // small delay to let modal hide animation finish (optional)
    setTimeout(() => {
      if (pendingLogoutUrl) {
        // if logout URL is on same server and is a POST endpoint, consider calling via fetch first
        window.location.href = pendingLogoutUrl;
      } else {
        window.location.href = 'LGU-2-MAIN/login.php';
      }
    }, 180);
  });

})();
