<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="{{ asset('image/logo.png') }}" type="image/png">
  <link rel="icon" href="{{ asset('image/logo.png') }}" type="image/png">
  <title>Login - Sanggara Management System</title>
  <link rel="stylesheet" href="{{ asset('css/global.css') }}">
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
  <script src="{{ asset('js/global.js') }}"></script>
</head>
<body>
  <div class="login-page">
    <!-- Left Hero Banner -->
    <div class="login-brand-panel">
      <div class="brand-content">
        <h1 class="brand-hero-title">Sanggar Bunda Febri Management System</h1>
        <p class="brand-hero-subtitle">Galeri SBF | Jln.Veteran gang keramik perum cihideung indah blok A1</p>
      </div>
    </div>

    <!-- Right Login Form -->
    <div class="login-form-panel">
      <div class="login-card">
        <div class="login-header">
          <div class="login-logo">sanggara<span>.</span></div>
          <div class="login-subtitle">Masuk ke Dashboard Sistem</div>
        </div>

        @if(session('error'))
          <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 12px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>{{ session('error') }}</span>
          </div>
        @endif

        <form class="login-form" action="{{ url('/login') }}" method="POST">
          @csrf
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-input" value="{{ old('email', 'muhamadsolehs871@gmail.com') }}" placeholder="masukkan email anda" required autofocus>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <div class="input-with-icon">
              <input type="password" id="password" name="password" class="form-input" value="Sandi123!" placeholder="masukkan kata sandi" required>
              <button type="button" class="toggle-password-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <a href="#" class="forgot-password-link">Lupa kata sandi?</a>

          <button type="submit" class="btn btn-login">Masuk ke Sistem</button>
        </form>

        <div class="login-footer">
          <div class="footer-links">
            <a href="#">Privacy Policy</a> • <a href="#">Terms and Condition</a>
          </div>
          <div class="footer-copy">2026. Pratama Solusi Teknologi</div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && pwdInput) {
      toggleBtn.addEventListener('click', () => {
        const isPassword = pwdInput.type === 'password';
        pwdInput.type = isPassword ? 'text' : 'password';
        
        if (isPassword) {
          eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
        } else {
          eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
        }
      });
    }
  </script>
</body>
</html>
