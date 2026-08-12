<?php
require_once __DIR__ . '/../config/db.php';

// Session Guard
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user']['role'] !== 'Farmer') {
    header("Location: buyer-dashboard.php");
    exit();
}

// Fetch fresh user and farm data from database
$userId = $_SESSION['user']['id'];
$userRows = db_query("SELECT full_name, email, mobile_number, address, city, province FROM users WHERE id = ?", [$userId], "i");
$farmRows = db_query("SELECT farm_name, farm_location, crop_type FROM farms WHERE user_id = ?", [$userId], "i");

if (!$userRows || count($userRows) === 0) {
    // Session user doesn't exist anymore in DB
    header("Location: api/logout.php");
    exit();
}

$user = $userRows[0];
$farm = ($farmRows && count($farmRows) > 0) ? $farmRows[0] : [
    "farm_name" => "Not Specified",
    "farm_location" => "Not Specified",
    "crop_type" => "Not Specified"
];

// Generate initials for avatar
$words = explode(" ", $user['full_name']);
$initials = "";
foreach ($words as $w) {
    $initials .= isset($w[0]) ? strtoupper($w[0]) : '';
}
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farmer Dashboard - Digital Mandi</title>
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
<body class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white min-h-screen flex flex-col font-sans">
  
  <div class="max-w-7xl mx-auto px-6 w-full flex-grow flex flex-col">
    <!-- Header -->
    <header class="flex justify-between items-center py-6 border-b border-white/10 mb-10">
      <a href="farmer-dashboard.php" class="flex items-center gap-3 text-2xl font-bold">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-lg shadow-lg">🌿</div>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <div class="flex items-center gap-4">
        <span class="hidden md:inline text-sm text-slate-300">Welcome back, <strong class="text-white"><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
        <button id="logoutBtn" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-sm font-semibold transition duration-200">Log Out</button>
      </div>
    </header>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-8 flex-grow">
      
      <!-- Profile Card (Left Column) -->
      <aside class="bg-white/[0.03] border border-white/10 rounded-2xl p-6 backdrop-blur-xl shadow-2xl h-fit flex flex-col items-center text-center">
        <!-- Avatar -->
        <div class="w-24 h-24 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-3xl font-bold text-white mb-4 border-4 border-white/10 shadow-lg">
          <?php echo htmlspecialchars($initials); ?>
        </div>
        <h3 class="text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <span class="px-3 py-1 bg-emerald-950/40 border border-emerald-500/30 text-emerald-400 text-xs font-semibold rounded-full mb-6">Farmer</span>
        
        <!-- Profile Meta -->
        <div class="w-full text-left space-y-4 border-t border-white/10 pt-5 text-sm">
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Email</span>
            <span class="text-slate-200 font-medium break-all"><?php echo htmlspecialchars($user['email']); ?></span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Mobile Number</span>
            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($user['mobile_number']); ?></span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Address</span>
            <span class="text-slate-200 font-medium leading-relaxed"><?php echo htmlspecialchars($user['address']); ?></span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Location</span>
            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($user['city'] . ', ' . $user['province']); ?></span>
          </div>
        </div>

        <!-- Farm details -->
        <div class="w-full text-left space-y-4 border-t border-dashed border-white/10 pt-5 mt-5 text-sm">
          <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-2">Farm Metadata</h4>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Farm Name</span>
            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($farm['farm_name']); ?></span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Farm Location</span>
            <span class="text-slate-200 font-medium leading-relaxed"><?php echo htmlspecialchars($farm['farm_location']); ?></span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Primary Crop Type</span>
            <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($farm['crop_type']); ?></span>
          </div>
        </div>

        <a href="edit-profile.php" class="w-full bg-white/5 hover:bg-white/10 text-white font-semibold py-3 rounded-xl border border-white/10 transition duration-200 text-center text-sm mt-6">
          ✏️ Edit Profile
        </a>
      </aside>

      <!-- Dashboard Main (Right Column) -->
      <main class="space-y-6">
        
        <!-- Welcome banner -->
        <section class="bg-gradient-to-r from-emerald-950/40 to-slate-900 border border-white/10 rounded-2xl p-8 backdrop-blur-xl shadow-2xl">
          <h2 class="text-2xl font-bold mb-2">Welcome Muhammad Ali</h2>
          <p class="text-slate-300 text-sm md:text-base leading-relaxed">
            This is your Digital Mandi Farmer Dashboard. From here you can manage your farm profile, list your fresh harvests, view incoming buyer requests, and track your agricultural trade directly.
          </p>
        </section>

        <!-- Stats widgets -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div class="bg-white/[0.02] border border-white/5 rounded-xl p-5 flex flex-col">
            <span class="text-2xl mb-2">🚜</span>
            <span class="text-2xl font-bold text-white mb-1">1</span>
            <span class="text-xs text-slate-400 font-medium">Active Farm Profile</span>
          </div>
          <div class="bg-white/[0.02] border border-white/5 rounded-xl p-5 flex flex-col">
            <span class="text-2xl mb-2">📦</span>
            <span class="text-2xl font-bold text-white mb-1">0</span>
            <span class="text-xs text-slate-400 font-medium">Active Crop Listings</span>
          </div>
          <div class="bg-white/[0.02] border border-white/5 rounded-xl p-5 flex flex-col">
            <span class="text-2xl mb-2">💰</span>
            <span class="text-2xl font-bold text-white mb-1">Rs 0</span>
            <span class="text-xs text-slate-400 font-medium">Total Earned Revenue</span>
          </div>
        </section>

        <!-- Listings widget placeholder -->
        <section class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 shadow-2xl">
          <h3 class="text-lg font-bold text-white mb-4">My Produce Listings</h3>
          <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-sm border-collapse">
              <thead>
                <tr class="border-b border-white/10 text-slate-400">
                  <th class="py-3 font-semibold">Crop</th>
                  <th class="py-3 font-semibold">Quantity</th>
                  <th class="py-3 font-semibold">Price per Unit</th>
                  <th class="py-3 font-semibold">Harvest Location</th>
                  <th class="py-3 font-semibold">Listing Status</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-b border-white/5 last:border-b-0 text-slate-300">
                  <td colspan="5" class="py-8 text-center text-slate-500">
                    No active listings added yet. Start by creating a listing when the product module is launched.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>

    </div>

    <!-- Footer -->
    <footer class="text-center py-8 border-t border-white/10 mt-12 text-slate-500 text-sm">
      <p>&copy; 2026 Digital Mandi. Empowering the Agricultural Supply Chain in Pakistan.</p>
    </footer>
  </div>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-3"></div>

  <!-- Loading Overlay -->
  <div id="loading-overlay" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-12 h-12 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    function showToast(message, type = 'success') {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      const isError = type === 'error';
      
      toast.className = `flex items-center gap-3 px-5 py-4 rounded-xl shadow-2xl border backdrop-blur-md transition-all duration-300 transform translate-x-full opacity-0 bg-emerald-950/90 border-emerald-500/30 text-white`;
      
      toast.innerHTML = `<span class="text-lg">✓</span><span class="text-sm font-medium">${message}</span>`;
      container.appendChild(toast);

      requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
      });

      setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.addEventListener('transitionend', () => toast.remove());
      }, 4000);
    }

    // Handle logout click
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      const overlay = document.getElementById('loading-overlay');
      overlay.classList.replace('hidden', 'flex');
      
      try {
        const response = await fetch('api/logout.php', { method: 'POST' });
        overlay.classList.replace('flex', 'hidden');
        if (response.ok) {
          showToast('Logged out successfully');
          setTimeout(() => {
            window.location.href = 'login.php';
          }, 800);
        } else {
          alert('Logout failed');
        }
      } catch (err) {
        overlay.classList.replace('flex', 'hidden');
        alert('Network error during logout');
      }
    });
  </script>
</body>
</html>
