<?php
require_once __DIR__ . '/config/db.php';

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
  <title>Register - Digital Mandi</title>
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
  
  <div class="w-full max-w-xl bg-white/[0.03] border border-white/10 rounded-3xl backdrop-blur-xl shadow-2xl p-8 relative my-8">
    
    <!-- Top Decorative Line -->
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 to-amber-500 rounded-t-3xl"></div>

    <div class="text-center mb-8">
      <a href="index.php" class="inline-flex items-center gap-2 text-xl font-bold mb-4">
        <span class="text-2xl">🌿</span>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <h2 class="text-2xl font-bold mb-2">Create Account</h2>
      <p class="text-slate-400 text-sm">Register as a Farmer or Buyer to join the digital mandi</p>
    </div>

    <!-- Registration Form -->
    <form id="registerForm" class="space-y-5" novalidate>
      
      <!-- Role Selector -->
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-3">Select User Role</label>
        <div class="grid grid-cols-2 gap-4">
          <label class="relative cursor-pointer">
            <input type="radio" name="role" value="Buyer" class="peer sr-only" checked>
            <div class="flex flex-col items-center p-4 bg-white/[0.01] border border-white/10 rounded-xl hover:bg-white/[0.04] peer-checked:border-emerald-500 peer-checked:bg-emerald-950/20 transition-all duration-200">
              <span class="text-2xl mb-1">🛒</span>
              <span class="text-sm font-semibold text-slate-400 peer-checked:text-white">Buyer</span>
            </div>
          </label>

          <label class="relative cursor-pointer">
            <input type="radio" name="role" value="Farmer" class="peer sr-only">
            <div class="flex flex-col items-center p-4 bg-white/[0.01] border border-white/10 rounded-xl hover:bg-white/[0.04] peer-checked:border-emerald-500 peer-checked:bg-emerald-950/20 transition-all duration-200">
              <span class="text-2xl mb-1">🚜</span>
              <span class="text-sm font-semibold text-slate-400 peer-checked:text-white">Farmer</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Common Fields -->
      <div>
        <label for="fullName" class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
        <input type="text" id="fullName" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Muhammad Ali" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
          <input type="email" id="email" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="name@example.com" required>
        </div>
        <div>
          <label for="mobileNumber" class="block text-sm font-medium text-slate-300 mb-2">Mobile Number</label>
          <input type="tel" id="mobileNumber" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="03001234567" required>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
          <input type="password" id="password" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="••••••••" required>
        </div>
        <div>
          <label for="confirmPassword" class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
          <input type="password" id="confirmPassword" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="••••••••" required>
        </div>
      </div>

      <div>
        <label for="address" class="block text-sm font-medium text-slate-300 mb-2">Complete Address</label>
        <input type="text" id="address" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="House 12, Street 3" required>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="city" class="block text-sm font-medium text-slate-300 mb-2">City</label>
          <input type="text" id="city" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Okara" required>
        </div>
        <div>
          <label for="province" class="block text-sm font-medium text-slate-300 mb-2">Province</label>
          <input type="text" id="province" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Punjab" required>
        </div>
      </div>

      <!-- Farmer-Specific Section (Collapsible using CSS transition) -->
      <div id="farmerFields" class="max-h-0 opacity-0 overflow-hidden transition-all duration-300 border-t border-dashed border-white/10 pt-0 space-y-4">
        <h3 class="text-emerald-400 font-semibold text-sm pt-2">Farm Information</h3>
        
        <div>
          <label for="farmName" class="block text-sm font-medium text-slate-300 mb-2">Farm Name</label>
          <input type="text" id="farmName" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Ali Farms">
        </div>

        <div>
          <label for="farmLocation" class="block text-sm font-medium text-slate-300 mb-2">Farm Location (Address/Tehsil)</label>
          <input type="text" id="farmLocation" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Okara Bypass">
        </div>

        <div>
          <label for="cropType" class="block text-sm font-medium text-slate-300 mb-2">Primary Crop Type</label>
          <input type="text" id="cropType" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" placeholder="Wheat, Rice, Cotton">
        </div>
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-emerald-950/50 hover:shadow-emerald-500/10 transition duration-200 mt-6">
        Create Account
      </button>
    </form>

    <div class="text-center mt-6 text-sm text-slate-400">
      Already have an account? <a href="login.php" class="text-emerald-400 hover:underline hover:text-emerald-300 font-semibold transition">Log In</a>
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

    // Role Switching Transition Logic
    const roleRadios = document.querySelectorAll('input[name="role"]');
    const farmerFields = document.getElementById('farmerFields');
    const farmName = document.getElementById('farmName');
    const farmLocation = document.getElementById('farmLocation');
    const cropType = document.getElementById('cropType');

    roleRadios.forEach(radio => {
      radio.addEventListener('change', (e) => {
        if (e.target.value === 'Farmer') {
          farmerFields.classList.remove('max-h-0', 'opacity-0');
          farmerFields.classList.add('max-h-[500px]', 'opacity-100', 'mt-4');
          farmName.setAttribute('required', 'true');
          farmLocation.setAttribute('required', 'true');
          cropType.setAttribute('required', 'true');
        } else {
          farmerFields.classList.add('max-h-0', 'opacity-0');
          farmerFields.classList.remove('max-h-[500px]', 'opacity-100', 'mt-4');
          farmName.removeAttribute('required');
          farmLocation.removeAttribute('required');
          cropType.removeAttribute('required');
        }
      });
    });

    const form = document.getElementById('registerForm');
    const overlay = document.getElementById('loading-overlay');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const role = document.querySelector('input[name="role"]:checked').value;
      const fullName = document.getElementById('fullName').value.trim();
      const email = document.getElementById('email').value.trim();
      const mobileNumber = document.getElementById('mobileNumber').value.trim();
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      const address = document.getElementById('address').value.trim();
      const city = document.getElementById('city').value.trim();
      const province = document.getElementById('province').value.trim();

      // Front-end validations
      if (!fullName || !email || !mobileNumber || !password || !confirmPassword || !address || !city || !province) {
        showToast('Please fill in all mandatory fields', 'error');
        return;
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
      }

      if (!/^\d{10,15}$/.test(mobileNumber.replace(/[-+ ]/g, ''))) {
        showToast('Please enter a valid mobile number (10-15 digits)', 'error');
        return;
      }

      // Password policy
      if (password.length < 8 || !/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
        showToast('Password must be at least 8 characters and contain both letters and numbers', 'error');
        return;
      }

      if (password !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
      }

      // Farmer specific details
      let farmNameVal = '';
      let farmLocationVal = '';
      let cropTypeVal = '';

      if (role === 'Farmer') {
        farmNameVal = farmName.value.trim();
        farmLocationVal = farmLocation.value.trim();
        cropTypeVal = cropType.value.trim();

        if (!farmNameVal || !farmLocationVal || !cropTypeVal) {
          showToast('Please fill in all farm details', 'error');
          return;
        }
      }

      overlay.classList.replace('hidden', 'flex');

      const payload = {
        role,
        fullName,
        email,
        mobileNumber,
        password,
        address,
        city,
        province,
        farmName: farmNameVal,
        farmLocation: farmLocationVal,
        cropType: cropTypeVal
      };

      try {
        const response = await fetch('api/register.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();
        overlay.classList.replace('flex', 'hidden');

        if (response.ok) {
          showToast(data.message, 'success');
          setTimeout(() => {
            window.location.href = 'login.php';
          }, 1500);
        } else {
          showToast(data.message || 'Registration failed', 'error');
        }
      } catch (error) {
        overlay.classList.replace('flex', 'hidden');
        showToast('Server connection failed. Try again.', 'error');
      }
    });
  </script>
</body>
</html>
