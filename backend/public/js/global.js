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


/* ==========================================================================
   SANGGARA THEMED MODAL & TOAST DIALOG SYSTEM
   Dialog popup terintegrasi & pengganti alert / confirm bawaan browser
   ========================================================================== */

const SanggaraModal = {
  _activeResolver: null,

  ensureDOM() {
    let overlay = document.getElementById('sanggaraDialogOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sanggaraDialogOverlay';
      overlay.className = 'sanggara-dialog-overlay';
      overlay.innerHTML = `
        <div class="sanggara-dialog-box" id="sanggaraDialogBox" role="dialog" aria-modal="true">
          <div class="sanggara-dialog-icon-wrapper" id="sanggaraDialogIcon"></div>
          <h3 class="sanggara-dialog-title" id="sanggaraDialogTitle"></h3>
          <p class="sanggara-dialog-message" id="sanggaraDialogMessage"></p>
          <div class="sanggara-dialog-actions" id="sanggaraDialogActions"></div>
        </div>
      `;
      document.body.appendChild(overlay);

      overlay.addEventListener('click', (e) => {
        if (e.target === overlay && overlay.dataset.allowBackdrop === 'true') {
          SanggaraModal.close(null);
        }
      });
    }

    let toastContainer = document.getElementById('sanggaraToastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'sanggaraToastContainer';
      toastContainer.className = 'sanggara-toast-container';
      document.body.appendChild(toastContainer);
    }
  },

  getIcon(type) {
    switch (type) {
      case 'success':
        return `<div class="sanggara-icon-circle sanggara-icon-success">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>`;
      case 'logout':
        return `<div class="sanggara-icon-circle sanggara-icon-danger" style="background-color: #fee2e2; color: #ef4444;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </div>`;
      case 'danger':
      case 'delete':
      case 'error':
        return `<div class="sanggara-icon-circle sanggara-icon-danger">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
          </svg>
        </div>`;
      case 'warning':
        return `<div class="sanggara-icon-circle sanggara-icon-warning">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>`;
      case 'info':
      default:
        return `<div class="sanggara-icon-circle sanggara-icon-info">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
        </div>`;
    }
  },

  alert(message, options = {}) {
    this.ensureDOM();
    const overlay = document.getElementById('sanggaraDialogOverlay');
    const iconEl = document.getElementById('sanggaraDialogIcon');
    const titleEl = document.getElementById('sanggaraDialogTitle');
    const messageEl = document.getElementById('sanggaraDialogMessage');
    const actionsEl = document.getElementById('sanggaraDialogActions');

    const msgLower = String(message).toLowerCase();
    const isSuccess = msgLower.includes('berhasil') || msgLower.includes('sukses');
    const isError = msgLower.includes('gagal') || msgLower.includes('error') || msgLower.includes('tidak ditemukan');
    const type = options.type || (isSuccess ? 'success' : (isError ? 'danger' : 'info'));
    
    const defaultTitle = type === 'success' ? 'Berhasil' : (type === 'danger' ? 'Terjadi Kesalahan' : 'Pemberitahuan');
    const title = options.title || defaultTitle;
    const btnText = options.buttonText || (type === 'success' ? 'OK, Lanjutkan' : 'Tutup');

    iconEl.innerHTML = this.getIcon(type);
    titleEl.textContent = title;
    messageEl.textContent = message;
    overlay.dataset.allowBackdrop = 'true';

    return new Promise((resolve) => {
      actionsEl.innerHTML = `
        <button type="button" class="sanggara-dialog-btn sanggara-dialog-btn-primary" id="sanggaraDialogOkBtn">
          ${btnText}
        </button>
      `;

      const okBtn = document.getElementById('sanggaraDialogOkBtn');
      const handleClose = () => {
        overlay.classList.remove('active');
        resolve(true);
      };

      okBtn.onclick = handleClose;
      this._activeResolver = resolve;

      overlay.classList.add('active');
      setTimeout(() => okBtn.focus(), 50);
    });
  },

  confirm(message, options = {}) {
    this.ensureDOM();
    const overlay = document.getElementById('sanggaraDialogOverlay');
    const iconEl = document.getElementById('sanggaraDialogIcon');
    const titleEl = document.getElementById('sanggaraDialogTitle');
    const messageEl = document.getElementById('sanggaraDialogMessage');
    const actionsEl = document.getElementById('sanggaraDialogActions');

    const msgLower = String(message).toLowerCase();
    const isLogout = options.type === 'logout' || msgLower.includes('keluar') || msgLower.includes('logout');
    const isDelete = options.type === 'danger' || options.type === 'delete' || msgLower.includes('hapus');
    const type = options.type || (isLogout ? 'logout' : (isDelete ? 'danger' : 'warning'));
    const defaultTitle = isLogout ? 'Konfirmasi Logout' : (isDelete ? 'Konfirmasi Hapus' : 'Konfirmasi Tindakan');
    const title = options.title || defaultTitle;
    const confirmText = options.confirmText || (isLogout ? 'Ya, Keluar' : (isDelete ? 'Ya, Hapus' : 'Ya, Lanjutkan'));
    const cancelText = options.cancelText || 'Batal';
    const confirmBtnClass = (isDelete || isLogout) ? 'sanggara-dialog-btn-danger' : 'sanggara-dialog-btn-primary';

    iconEl.innerHTML = this.getIcon(type);
    titleEl.textContent = title;
    messageEl.textContent = message;
    overlay.dataset.allowBackdrop = 'false';

    return new Promise((resolve) => {
      actionsEl.innerHTML = `
        <button type="button" class="sanggara-dialog-btn sanggara-dialog-btn-secondary" id="sanggaraDialogCancelBtn">
          ${cancelText}
        </button>
        <button type="button" class="sanggara-dialog-btn ${confirmBtnClass}" id="sanggaraDialogConfirmBtn">
          ${confirmText}
        </button>
      `;

      const cancelBtn = document.getElementById('sanggaraDialogCancelBtn');
      const confirmBtn = document.getElementById('sanggaraDialogConfirmBtn');

      cancelBtn.onclick = () => {
        overlay.classList.remove('active');
        resolve(false);
      };

      confirmBtn.onclick = () => {
        overlay.classList.remove('active');
        resolve(true);
      };

      this._activeResolver = resolve;
      overlay.classList.add('active');
      setTimeout(() => confirmBtn.focus(), 50);
    });
  },

  close(val = false) {
    const overlay = document.getElementById('sanggaraDialogOverlay');
    if (overlay) overlay.classList.remove('active');
    if (this._activeResolver) {
      this._activeResolver(val);
      this._activeResolver = null;
    }
  },

  toast(message, type = 'info', duration = 3000) {
    this.ensureDOM();
    const container = document.getElementById('sanggaraToastContainer');
    const toast = document.createElement('div');
    toast.className = `sanggara-toast sanggara-toast-${type}`;

    let iconSvg = '';
    if (type === 'success') {
      iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    } else if (type === 'danger' || type === 'error') {
      iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    } else {
      iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
    }

    toast.innerHTML = `
      <span class="sanggara-toast-icon">${iconSvg}</span>
      <span class="sanggara-toast-text">${message}</span>
    `;

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 250);
    }, duration);
  }
};

window.SanggaraModal = SanggaraModal;
window.showToast = (msg, type, dur) => SanggaraModal.toast(msg, type, dur);
window.showModalAlert = (msg, opts) => SanggaraModal.alert(msg, opts);
window.showModalConfirm = (msg, opts) => SanggaraModal.confirm(msg, opts);

// Override native window.alert agar panggilan alert() otomatis tampil dengan tema Sanggara
if (typeof window !== 'undefined') {
  window.alert = function(msg) {
    if (typeof msg !== 'string') msg = String(msg);
    return SanggaraModal.alert(msg);
  };
}


/* ==========================================================================
   FITUR LOGOUT PENGGUNA (USER PROFILE MENU)
   Menampilkan popup validasi konfirmasi logout bertema Sanggara (bisa dicancel)
   ========================================================================== */

let isLogoutModalOpen = false;

async function handleUserLogout(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  if (isLogoutModalOpen) return;
  isLogoutModalOpen = true;

  try {
    const confirmed = await SanggaraModal.confirm('Apakah Anda yakin ingin keluar dari sistem Sanggara?', {
      title: 'Konfirmasi Logout',
      type: 'logout',
      confirmText: 'Ya, Keluar',
      cancelText: 'Batal'
    });

    if (!confirmed) {
      // Pengguna membatalkan logout (popup ditutup tanpa keluar)
      return;
    }

    // Bersihkan sesi pengguna
    try {
      localStorage.removeItem('sanggara_token');
      localStorage.removeItem('sanggara_user');
      sessionStorage.clear();
    } catch (err) {}

    SanggaraModal.toast('Anda telah berhasil keluar dari sistem.', 'success');

    // Redirect ke halaman login setelah jeda singkat
    setTimeout(() => {
      const isPagesDir = window.location.pathname.includes('/pages/');
      window.location.href = isPagesDir ? 'login.html' : 'pages/login.html';
    }, 600);
  } finally {
    isLogoutModalOpen = false;
  }
}

window.handleUserLogout = handleUserLogout;

// Set atribut aksesibilitas dan tooltip pada elemen user profile
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.user-profile, .btn-user-more').forEach(el => {
    el.setAttribute('title', 'Klik untuk Keluar / Logout');
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');
  });
});

// Listener klik delegasi untuk elemen profile user di header
document.addEventListener('click', function(e) {
  const profileTrigger = e.target.closest('.user-profile, .btn-user-more');
  if (profileTrigger) {
    handleUserLogout(e);
  }
});


/* ==========================================================================
   AUTO-INJECT APP FOOTER
   Memastikan setiap halaman memiliki footer dengan jarak proporsional
   ========================================================================== */

function ensureAppFooter() {
  const main = document.querySelector('.app-main');
  if (!main) return;

  let footer = main.querySelector('.app-footer');
  if (!footer) {
    footer = document.createElement('footer');
    footer.className = 'app-footer';
    footer.innerHTML = `
      <div class="footer-left">
        <span>&copy; 2026 <strong>Sanggar Bunda Febri (SBF)</strong> &bull; Sanggara Management System</span>
      </div>
      <div class="footer-right">
        <span>Powered by <strong>Pratama Solusi Teknologi</strong></span>
      </div>
    `;
    main.appendChild(footer);
  }
}

document.addEventListener('DOMContentLoaded', ensureAppFooter);
