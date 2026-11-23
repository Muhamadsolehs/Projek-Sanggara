/**
 * Sanggara Management System - Global UI Interaction
 * File: js/global.js
 * Fitur: Sidebar Toggle, State Persist, & Submenu Accordion
 */

function toggleSidebar() {
  const isClosed = document.body.classList.toggle('sidebar-closed');
  try {
    localStorage.setItem('sanggara_sidebar_closed', isClosed ? 'true' : 'false');
  } catch (e) {}
}

// Restore sidebar state immediately to prevent layout shifts
(function() {
  try {
    if (localStorage.getItem('sanggara_sidebar_closed') === 'true') {
      document.body.classList.add('sidebar-closed');
    }
  } catch (e) {}
})();

document.addEventListener('DOMContentLoaded', () => {
  // Bind toggle buttons if not already bound via onclick
  document.querySelectorAll('.btn-sidebar-toggle, .btn-header-sidebar-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleSidebar();
    });
  });

  // Handle collapsible sidebar submenus (Laporan & Analisa)
  document.querySelectorAll('.nav-group .nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      const parentGroup = this.closest('.nav-group');
      if (parentGroup) {
        parentGroup.classList.toggle('open');
      }
    });
  });
});
