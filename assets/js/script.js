document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Set active navigation based on current URL
  const currentPath = window.location.pathname;
  const activeLink = document.querySelector(`.nav-link[href="${currentPath}"]`);
  if (activeLink) {
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    activeLink.classList.add('active');
    
    // Open the parent nav group
    const group = activeLink.closest('.nav-group');
    if (group) group.classList.add('open');
  }

  // Mobile sidebar functionality
  const burger = document.getElementById('burger');
  const backdrop = document.getElementById('backdrop');
  const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

  if (burger) burger.addEventListener('click', () => {
    body.classList.toggle('mobile-open');
  });
  if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('mobile-open'));
  window.addEventListener('resize', () => { 
    if (!isMobile()) body.classList.remove('mobile-open');
  });

  // Group toggle functionality
  const groups = document.querySelectorAll('.nav-group');
  const storageKeyGroup = 'lgu_open_groups';
  const openGroups = new Set(JSON.parse(localStorage.getItem(storageKeyGroup) || '[]'));
  
  // Keep Dashboard always open & exclude it from being closed
  const dashboardHref = 'dashboard.php';
  const dashboardGroup = Array.from(groups).find(g =>
    !!g.querySelector(`.sublist a[href="${dashboardHref}"]`)
  );

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
      if (g === dashboardGroup) return;

      groups.forEach((otherG, otherIdx) => {
        if (otherG !== g && otherG !== dashboardGroup) {
          otherG.classList.remove('open');
          openGroups.delete(`g${otherIdx}`);
        }
      });

      g.classList.toggle('open');
      if (g.classList.contains('open')) {
        openGroups.add(key);
      } else {
        openGroups.delete(key);
      }
      localStorage.setItem(storageKeyGroup, JSON.stringify([...openGroups]));
    });
  });

  // Close mobile menu when clicking links
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      if (isMobile()) body.classList.remove('mobile-open');
    });
  });
});
