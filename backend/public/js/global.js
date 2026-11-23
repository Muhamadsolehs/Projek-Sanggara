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

let lastNavToggleTime = 0;
function toggleNavGroup(element) {
  const now = Date.now();
  if (now - lastNavToggleTime < 250) return; // Mencegah pemanggilan ganda dalam 250ms (double-toggle)
  lastNavToggleTime = now;

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

document.addEventListener('DOMContentLoaded', () => {
  // Handle collapsible sidebar submenus (Laporan & Analisa)
  // Menghapus atribut inline onclick untuk mencegah double-toggle dan memasang handler tunggal
  document.querySelectorAll('.nav-group > .nav-item').forEach(item => {
    item.removeAttribute('onclick');
    item.onclick = function(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      toggleNavGroup(this);
    };
  });

  // Auto-expand nav-group if current URL is inside submenu
  try {
    const currentFile = window.location.pathname.split('/').pop();
    if (currentFile) {
      document.querySelectorAll('.subnav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentFile)) {
          link.classList.add('active');
          const group = link.closest('.nav-group');
          if (group) group.classList.add('open');
        }
      });
    }
  } catch (e) {}
});
