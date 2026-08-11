<?php
require_once __DIR__ . '/config/db.php';

// Session Guard
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Fetch fresh details
$userRows = db_query("SELECT full_name, email, mobile_number, address, city, province FROM users WHERE id = ?", [$userId], "i");
if (!$userRows || count($userRows) === 0) {
    header("Location: api/logout.php");
    exit();
}

$user = $userRows[0];

$farm = [
    "farm_name" => "",
    "farm_location" => "",
    "crop_type" => ""
];

if ($userRole === 'Farmer') {
    $farmRows = db_query("SELECT farm_name, farm_location, crop_type FROM farms WHERE user_id = ?", [$userId], "i");
    if ($farmRows && count($farmRows) > 0) {
        $farm = $farmRows[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile - Digital Mandi</title>
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
      <h2 class="text-2xl font-bold mb-2">Edit Profile</h2>
      <p class="text-slate-400 text-sm">Update your personal and farm information details</p>
    </div>

    <!-- Edit Profile Form -->
    <form id="editProfileForm" class="space-y-5" novalidate>
      
      <!-- Account Role (Display Only) -->
      <div>
        <label class="block text-sm font-medium text-slate-400 mb-2">Account Role</label>
        <input type="text" value="<?php echo htmlspecialchars($userRole); ?>" class="w-full bg-white/[0.05] border border-white/5 rounded-xl px-4 py-3 text-slate-400 font-bold focus:outline-none cursor-not-allowed" readonly>
      </div>

      <!-- Common Fields -->
      <div>
        <label for="fullName" class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
        <input type="text" id="fullName" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-slate-400 mb-2">Email Address (Read-only)</label>
        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-white/[0.05] border border-white/5 rounded-xl px-4 py-3 text-slate-400 focus:outline-none cursor-not-allowed" readonly>
      </div>

      <div>
        <label for="mobileNumber" class="block text-sm font-medium text-slate-300 mb-2">Mobile Number</label>
        <input type="tel" id="mobileNumber" value="<?php echo htmlspecialchars($user['mobile_number']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
      </div>

      <div>
        <label for="address" class="block text-sm font-medium text-slate-300 mb-2">Complete Address</label>
        <input type="text" id="address" value="<?php echo htmlspecialchars($user['address']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="city" class="block text-sm font-medium text-slate-300 mb-2">City</label>
          <input type="text" id="city" value="<?php echo htmlspecialchars($user['city']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
        </div>
        <div>
          <label for="province" class="block text-sm font-medium text-slate-300 mb-2">Province</label>
          <input type="text" id="province" value="<?php echo htmlspecialchars($user['province']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
        </div>
      </div>

      <!-- Farmer-Specific Section -->
      <?php if ($userRole === 'Farmer'): ?>
      <div class="border-t border-dashed border-white/10 pt-4 space-y-4">
        <h3 class="text-emerald-400 font-semibold text-sm pt-2">Farm Information</h3>
        
        <div>
          <label for="farmName" class="block text-sm font-medium text-slate-300 mb-2">Farm Name</label>
          <input type="text" id="farmName" value="<?php echo htmlspecialchars($farm['farm_name']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
        </div>

        <div>
          <label for="farmLocation" class="block text-sm font-medium text-slate-300 mb-2">Farm Location (Address/Tehsil)</label>
          <input type="text" id="farmLocation" value="<?php echo htmlspecialchars($farm['farm_location']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
        </div>

        <div>
          <label for="cropType" class="block text-sm font-medium text-slate-300 mb-2">Primary Crop Type</label>
          <input type="text" id="cropType" value="<?php echo htmlspecialchars($farm['crop_type']); ?>" class="w-full bg-white/[0.02] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition duration-200" required>
        </div>
      </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="grid grid-cols-2 gap-4 mt-6 pt-2">
        <button type="button" id="cancelBtn" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold py-3.5 rounded-xl transition duration-200">
          Cancel
        </button>
        <button type="submit" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-emerald-950/50 hover:shadow-emerald-500/10 transition duration-200">
          Save Changes
        </button>
      </div>
    </form>
  </div>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-3"></div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-12 h-12 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    const userRole = "<?php echo $userRole; ?>";

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

      requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
      });

      setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.addEventListener('transitionend', () => toast.remove());
      }, 4000);
    }

    // Cancel Button Click
    document.getElementById('cancelBtn').addEventListener('click', () => {
      window.location.href = userRole === 'Farmer' ? 'farmer-dashboard.php' : 'buyer-dashboard.php';
    });

    const form = document.getElementById('editProfileForm');
    const overlay = document.getElementById('loading-overlay');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const fullName = document.getElementById('fullName').value.trim();
      const mobileNumber = document.getElementById('mobileNumber').value.trim();
      const address = document.getElementById('address').value.trim();
      const city = document.getElementById('city').value.trim();
      const province = document.getElementById('province').value.trim();

      // Front-end validations
      if (!fullName || !mobileNumber || !address || !city || !province) {
        showToast('Please fill in all mandatory fields', 'error');
        return;
      }

      if (!/^\d{10,15}$/.test(mobileNumber.replace(/[-+ ]/g, ''))) {
        showToast('Please enter a valid mobile number (10-15 digits)', 'error');
        return;
      }

      let farmNameVal = '';
      let farmLocationVal = '';
      let cropTypeVal = '';

      if (userRole === 'Farmer') {
        farmNameVal = document.getElementById('farmName').value.trim();
        farmLocationVal = document.getElementById('farmLocation').value.trim();
        cropTypeVal = document.getElementById('cropType').value.trim();

        if (!farmNameVal || !farmLocationVal || !cropTypeVal) {
          showToast('Please fill in all farm details', 'error');
          return;
        }
      }

      overlay.classList.replace('hidden', 'flex');

      const payload = {
        fullName,
        mobileNumber,
        address,
        city,
        province,
        farmName: farmNameVal,
        farmLocation: farmLocationVal,
        cropType: cropTypeVal
      };

      try {
        const response = await fetch('api/profile.php', {
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
            window.location.href = userRole === 'Farmer' ? 'farmer-dashboard.php' : 'buyer-dashboard.php';
          }, 1000);
        } else {
          showToast(data.message || 'Update failed', 'error');
        }
      } catch (error) {
        overlay.classList.replace('flex', 'hidden');
        showToast('Server connection failed. Try again.', 'error');
      }
    });
  </script>
</body>
</html>
