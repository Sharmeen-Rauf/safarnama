<?php
require_once __DIR__ . '/../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'Farmer' ? 'farmer-dashboard.php' : 'buyer-dashboard.php';
    header("Location: $redirect");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Digital Mandi</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Outfit', 'sans-serif'],
          }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white min-h-screen flex items-center justify-center font-sans p-6">
  
  <div class="w-full max-w-md bg-white/[0.03] border border-white/10 rounded-3xl backdrop-blur-xl shadow-2xl p-8 relative">
    
    <!-- Top Decorative Line -->
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 to-amber-500 rounded-t-3xl"></div>

    <div class="text-center mb-8">
      <a href="index.php" class="inline-flex items-center gap-2 text-xl font-bold mb-4">
        <span class="text-2xl">🌿</span>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <h2 class="text-2xl font-bold mb-2">Welcome Back</h2>
      <p class="text-slate-400 text-sm">Enter your credentials to access your Digital Mandi account</p>
    </div>

    <!-- Login Form -->
    <form id="loginForm" class="space-y-6" novalidate>
      <div>
        <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
        <input type="email" id="email" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="name@example.com" required>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
        <input type="password" id="password" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="••••••••" required>
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-emerald-950/50 hover:shadow-emerald-500/10 transition duration-200 mt-2">
        Sign In
      </button>
    </form>

    <div class="text-center mt-6 text-sm text-slate-400">
      Don't have an account? <a href="register.php" class="text-emerald-400 hover:underline hover:text-emerald-300 font-semibold transition">Create Account</a>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-3"></div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-12 h-12 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    // Toast Notification System
    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      const isError = type === 'error';
      
      toast.className = `flex items-center gap-3 px-5 py-4 rounded-xl shadow-2xl border backdrop-blur-md transition-all duration-300 transform translate-x-full opacity-0 ${
        isError 
          ? 'bg-red-950/90 border-red-500/30 text-white' 
          : 'bg-emerald-950/90 border-emerald-500/30 text-white'
      }`;
      
      const icon = isError ? '⚠️' : '✓';
      toast.innerHTML = `<span class="text-lg">${icon}</span><span class="text-sm font-medium">${message}</span>`;
      container.appendChild(toast);

      // Trigger slide-in animation
      requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
      });

      // Auto remove after 4 seconds
      setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.addEventListener('transitionend', () => toast.remove());
      }, 4000);
    }

    const form = document.getElementById('loginForm');
    const overlay = document.getElementById('loading-overlay');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;

      if (!email || !password) {
        showToast('Please fill in all fields', 'error');
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
      }

      overlay.classList.replace('hidden', 'flex');

      try {
        const response = await fetch('api/login.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ email, password })
        });

        const data = await response.json();
        overlay.classList.replace('flex', 'hidden');

        if (response.ok) {
          showToast(data.message, 'success');
          setTimeout(() => {
            if (data.user.role === 'Farmer') {
              window.location.href = 'farmer-dashboard.php';
            } else {
              window.location.href = 'buyer-dashboard.php';
            }
          }, 800);
        } else {
          showToast(data.message || 'Login failed', 'error');
        }
      } catch (error) {
        overlay.classList.replace('flex', 'hidden');
        showToast('Server connection failed. Try again.', 'error');
      }
    });
  </script>
</body>
</html>
