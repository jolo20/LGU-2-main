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

  
  document.querySelector('.settings-link').addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.getAttribute('data-href');
    loadLink(this);
  });
  document.querySelector('.profile-link').addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.getAttribute('data-href');
    loadLink(this);
  });

  // Expand/collapse groups + remember state
  const storageKeyGroup = 'lgu_open_groups';
  const openGroups = new Set(JSON.parse(localStorage.getItem(storageKeyGroup) || '[]'));
 // Keep Dashboard always open & exclude it from being closed by other group clicks
const dashboardHref = 'dashboard.php'; // <-- change this if your dashboard data-href is different
const dashboardGroup = Array.from(groups).find(g =>
  !!g.querySelector(`.sublist a[data-href="${dashboardHref}"]`)
);

// ensure dashboard group is open on load and recorded in openGroups
if (dashboardGroup) {
  const dashIdx = Array.from(groups).indexOf(dashboardGroup);
  dashboardGroup.classList.add('open');
  openGroups.add(`g${dashIdx}`);
  localStorage.setItem(storageKeyGroup, JSON.stringify([...openGroups]));
}

groups.forEach((g, idx) => {
  const btn = g.querySelector('.group-toggle');
  const key = `g${idx}`;
  if (openGroups.has(key)) g.classList.add('open');

  btn.addEventListener('click', () => {
    // If user clicked the dashboard group header, ignore — dashboard stays open
    if (g === dashboardGroup) return;

    // Close all other groups except the clicked group AND the dashboard group
    groups.forEach((otherG, otherIdx) => {
      if (otherG !== g && otherG !== dashboardGroup) {
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



  // AJAX load submodules
  const links = document.querySelectorAll('.sublist a, .settings-link, .profile-link');
  const storageKeyActive = 'lgu_active_href';

  function setActive(link) {
    document.querySelectorAll('.sublist a.active, .dropdown-item.active').forEach(a => a.classList.remove('active'));
    link.classList.add('active');
    const group = link.closest('.nav-group');
    if (group && !group.classList.contains('open')) group.classList.add('open');
  }

  async function loadLink(link, pushState = true) {
  const href = link.getAttribute('data-href');
  if (!href) return;

  setActive(link);

  content.innerHTML = `
    <div class="cardish">
      <div class="d-flex align-items-center gap-2">
        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
        <strong>Loading ${link.textContent.trim()}...</strong>
      </div>
    </div>
  `;

  try {
    const resp = await fetch(href, { cache: 'no-store' });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const html = await resp.text();
    content.innerHTML = html;
    localStorage.setItem(storageKeyActive, href);

    // ✅ Re-bind pagination links
    content.querySelectorAll(".pagination a").forEach(pagiLink => {
      pagiLink.addEventListener("click", e => {
        e.preventDefault();
        const url = pagiLink.getAttribute("href");
        if (url) {
          fetch(url)
            .then(r => r.text())
            .then(innerHtml => {
              content.innerHTML = innerHtml;

              // rebind pagination again after new load
              content.querySelectorAll(".pagination a").forEach(innerLink => {
                innerLink.addEventListener("click", e2 => {
                  e2.preventDefault();
                  fetch(innerLink.href)
                    .then(r => r.text())
                    .then(html => {
                      content.innerHTML = html;
                      window.history.pushState({}, "", innerLink.href);
                    });
                });
              });
            });
          window.history.pushState({}, "", url);
        }
      });
    });

    // Update URL
    if (pushState) {
      const u = new URL(window.location.href);
      u.searchParams.set('src', href);
      history.pushState({ src: href }, '', u.toString());
    }

  } catch (e) {
    content.innerHTML = `
      <div class="cardish">
        <h3 class="mb-2">Failed to load</h3>
        <p class="text-muted">Could not load: <code>${href}</code> (${e.message}).</p>
      </div>
    `;
    console.error(e);
  }
}

  links.forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      loadLink(a);
    });
    a.setAttribute('tabindex','0');
    a.addEventListener('keydown', ev => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); a.click(); }});
  });

  // Restore last visited or from URL ?src=
  const param = new URLSearchParams(window.location.search).get('src');
  const last = param || localStorage.getItem(storageKeyActive);
  if (last) {
    const found = Array.from(links).find(a => a.getAttribute('data-href') === last);
    if (found) loadLink(found, false);
  }

  // Popstate for back/forward
  window.addEventListener('popstate', (ev) => {
    const src = (ev.state && ev.state.src) || new URLSearchParams(window.location.search).get('src');
    if (!src) return;
    const found = Array.from(links).find(a => a.getAttribute('data-href') === src);
    if (found) loadLink(found, false);
  });

  // Handle search form submission
  document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'searchForm') {
      e.preventDefault();
      const searchValue = document.getElementById('searchInput').value.toLowerCase();
      
      // Get all document rows
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchValue)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  });

  // Handle search input for real-time filtering
  document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'searchInput') {
      const searchValue = e.target.value.toLowerCase();
      
      // Get all document rows
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchValue)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  });
});
// Event handlers for data-href elements are now handled in the DOMContentLoaded section above

const param = new URLSearchParams(window.location.search).get('src');
const last = param || localStorage.getItem(storageKeyActive) || 'dashboard.php';

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
      else pendingLogoutUrl = 'login.html'; // fallback

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
        window.location.href = 'login.html';
      }
    }, 180);
  });

})();
