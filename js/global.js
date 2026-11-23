/**
 * Sanggara Management System - Global UI Interaction
 * File: js/global.js
 * Fitur: Sidebar Toggle, State Persist, & Submenu Accordion
 */

function toggleSidebar() {
  const isClosed = document.body.classList.toggle('sidebar-closed');
  document.documentElement.classList.toggle('sidebar-closed', isClosed);
  try {
    localStorage.setItem('sanggara_sidebar_closed', isClosed ? 'true' : 'false');
  } catch (e) {}
}

function toggleNavGroup(element) {
  const group = element ? (element.closest('.nav-group') || element.parentElement) : null;
  if (group) {
    group.classList.toggle('open');
  }
}
window.toggleNavGroup = toggleNavGroup;

// Restore saved preference on load
(function() {
  try {
    if (localStorage.getItem('sanggara_sidebar_closed') === 'true') {
      document.documentElement.classList.add('sidebar-closed');
      document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('sidebar-closed');
      });
    }
  } catch (e) {}
})();

// Delegated single click listener for collapsible sidebar submenus (Laporan & Analisa)
document.addEventListener('click', function(e) {
  const trigger = e.target.closest('.nav-group > .nav-item');
  if (!trigger) return;

  e.preventDefault();
  e.stopPropagation();

  const group = trigger.closest('.nav-group');
  if (group) {
    group.classList.toggle('open');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // Pastikan tidak ada inline onclick tersisa yang memicu eksekusi ganda
  document.querySelectorAll('.nav-group > .nav-item').forEach(item => {
    item.removeAttribute('onclick');
    item.onclick = null;
  });

  // Auto-expand nav-group if current URL is inside submenu
  try {
    const currentFile = window.location.pathname.split('/').pop();
    if (currentFile) {
      document.querySelectorAll('.subnav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentFile || href.endsWith('/' + currentFile))) {
          link.classList.add('active');
          const group = link.closest('.nav-group');
          if (group) group.classList.add('open');
        }
      });
    }
  } catch (e) {}
});
