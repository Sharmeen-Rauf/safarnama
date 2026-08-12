<?php
require_once __DIR__ . '/../config/db.php';

// Session Guard: Verify Admin Role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header("Location: /login");
    exit();
}

$userId = $_SESSION['user']['id'];

// Auto-migration check: ensure 'is_blocked' column exists
global $conn;
if (!DB_MOCKED && $conn) {
    $columnCheck = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_blocked'");
    if ($columnCheck && mysqli_num_rows($columnCheck) === 0) {
        @mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_blocked BOOLEAN DEFAULT FALSE");
    }
}

// Fetch stats
$userCountVal   = db_query("SELECT COUNT(*) as count FROM users WHERE role != 'Admin'");
$productCountVal = db_query("SELECT COUNT(*) as count FROM products");
$orderCountVal   = db_query("SELECT COUNT(*) as count FROM orders");
$revenueVal      = db_query("SELECT SUM(total_price) as sum FROM orders WHERE status = 'Delivered'");

$stats = [
    "users"    => $userCountVal ? $userCountVal[0]['count'] : 0,
    "products" => $productCountVal ? $productCountVal[0]['count'] : 0,
    "orders"   => $orderCountVal ? $orderCountVal[0]['count'] : 0,
    "revenue"  => ($revenueVal && $revenueVal[0]['sum']) ? (float)$revenueVal[0]['sum'] : 0.0
];

// Fetch all users for moderation
$usersList = db_query(
    "SELECT id, full_name, email, mobile_number, city, province, role, is_blocked, created_at 
     FROM users 
     WHERE role != 'Admin' 
     ORDER BY id DESC"
) ?: [];

// Fetch all listings for moderation
$productsList = db_query(
    "SELECT p.*, u.full_name as farmer_name, c.name as category_name 
     FROM products p 
     JOIN users u ON p.farmer_id = u.id 
     JOIN categories c ON p.category_id = c.id 
     ORDER BY p.id DESC"
) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Digital Mandi</title>
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
  <style>
    .glass {
      background: rgba(255, 255, 255, 0.03);
      border: 1px rgba(255, 255, 255, 0.08) solid;
      backdrop-filter: blur(12px);
    }
  </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white min-h-screen flex flex-col font-sans relative overflow-x-hidden">
  
  <div class="max-w-7xl mx-auto px-6 w-full flex-grow flex flex-col">
    <!-- Header -->
    <header class="flex justify-between items-center py-6 border-b border-white/10 mb-10">
      <a href="/" class="flex items-center gap-3 text-2xl font-bold">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-lg shadow-lg">🌿</div>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <div class="flex items-center gap-4">
        <span class="hidden md:inline text-sm text-slate-300">Welcome, <strong class="text-white">System Admin</strong></span>
        <button onclick="handleLogout()" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-sm font-semibold transition duration-200">Log Out</button>
      </div>
    </header>

    <!-- Admin Welcome Banner -->
    <section class="glass rounded-2xl p-6 shadow-2xl mb-8">
      <h2 class="text-xl font-bold mb-1">🛠️ Platform Administration Control Panel</h2>
      <p class="text-xs text-slate-300 leading-relaxed">
        Monitor system-wide metrics, approve or block farmer/buyer accounts, and moderate crop listings to maintain platform security.
      </p>
    </section>

    <!-- Statistics Panel -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="glass rounded-xl p-4 flex flex-col">
        <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Total Users</span>
        <span class="text-2xl font-extrabold text-white mt-1"><?php echo $stats['users']; ?></span>
      </div>
      <div class="glass rounded-xl p-4 flex flex-col">
        <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Total Listings</span>
        <span class="text-2xl font-extrabold text-white mt-1"><?php echo $stats['products']; ?></span>
      </div>
      <div class="glass rounded-xl p-4 flex flex-col">
        <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Orders Tracked</span>
        <span class="text-2xl font-extrabold text-amber-400 mt-1"><?php echo $stats['orders']; ?></span>
      </div>
      <div class="glass rounded-xl p-4 flex flex-col">
        <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Mandi Revenue</span>
        <span class="text-2xl font-extrabold text-emerald-400 mt-1">Rs. <?php echo number_format($stats['revenue'], 2); ?></span>
      </div>
    </section>

    <!-- Two Column Worksheets (Users vs listings) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 flex-grow">
      
      <!-- Users moderation panel (FR10) -->
      <section class="glass rounded-2xl p-6 shadow-2xl h-fit">
        <h3 class="font-bold text-white text-base mb-4">👥 User Account Moderation</h3>
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-white/10 text-slate-400 font-semibold uppercase">
                <th class="py-2.5">Name</th>
                <th class="py-2.5">Role</th>
                <th class="py-2.5">Location</th>
                <th class="py-2.5">Status</th>
                <th class="py-2.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($usersList) > 0): ?>
                <?php foreach ($usersList as $usr): ?>
                  <tr class="border-b border-white/5 last:border-b-0 text-slate-200 hover:bg-white/2 transition">
                    <td class="py-3">
                      <span class="font-bold block"><?php echo htmlspecialchars($usr['full_name']); ?></span>
                      <span class="text-[10px] text-slate-500 block"><?php echo htmlspecialchars($usr['email']); ?></span>
                    </td>
                    <td class="py-3">
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase <?php echo $usr['role'] === 'Farmer' ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/20' : 'bg-amber-950 text-amber-400 border border-amber-500/20'; ?>">
                        <?php echo $usr['role']; ?>
                      </span>
                    </td>
                    <td class="py-3 text-slate-400"><?php echo htmlspecialchars($usr['city']); ?></td>
                    <td class="py-3">
                      <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold <?php echo $usr['is_blocked'] ? 'bg-red-950 text-red-400 border border-red-500/20' : 'bg-emerald-950 text-emerald-400 border border-emerald-500/20'; ?>">
                        <?php echo $usr['is_blocked'] ? 'Blocked' : 'Active'; ?>
                      </span>
                    </td>
                    <td class="py-3 text-right">
                      <button onclick="handleToggleBlock(<?php echo $usr['id']; ?>, '<?php echo htmlspecialchars($usr['full_name']); ?>')" class="font-bold py-1 px-2.5 rounded text-[10px] transition border <?php echo $usr['is_blocked'] ? 'bg-emerald-600 hover:bg-emerald-500 text-white border-transparent' : 'bg-red-950/40 hover:bg-red-900/50 text-red-400 border-red-500/20'; ?>">
                        <?php echo $usr['is_blocked'] ? 'Unblock' : 'Block'; ?>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-500 italic">No users found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Crop produce listings moderation panel (FR10) -->
      <section class="glass rounded-2xl p-6 shadow-2xl h-fit">
        <h3 class="font-bold text-white text-base mb-4">🌾 Product Listings Moderation</h3>
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-white/10 text-slate-400 font-semibold uppercase">
                <th class="py-2.5">Crop</th>
                <th class="py-2.5">Farmer</th>
                <th class="py-2.5">Price</th>
                <th class="py-2.5">Status</th>
                <th class="py-2.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($productsList) > 0): ?>
                <?php foreach ($productsList as $prod): ?>
                  <tr class="border-b border-white/5 last:border-b-0 text-slate-200 hover:bg-white/2 transition">
                    <td class="py-3">
                      <span class="font-bold block"><?php echo htmlspecialchars($prod['title']); ?></span>
                      <span class="text-[10px] text-slate-400 block"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                    </td>
                    <td class="py-3 text-slate-300 font-medium"><?php echo htmlspecialchars($prod['farmer_name']); ?></td>
                    <td class="py-3 text-emerald-400">Rs. <?php echo number_format($prod['price'], 2); ?></td>
                    <td class="py-3">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-950/40 border border-emerald-500/20 text-emerald-400">
                        <?php echo htmlspecialchars($prod['status']); ?>
                      </span>
                    </td>
                    <td class="py-3 text-right">
                      <button onclick="handleDeleteProductListing(<?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['title']); ?>')" class="text-red-400 hover:text-red-300 font-bold text-[10px] transition">
                        Remove
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-500 italic">No crop listings found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    </div>

    <!-- Footer -->
    <footer class="text-center py-8 border-t border-white/10 mt-12 text-slate-500 text-sm">
      <p>&copy; 2026 Digital Mandi. Empowering the Agricultural Supply Chain in Pakistan.</p>
    </footer>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-10 h-10 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    async function handleToggleBlock(targetUserId, fullName) {
      if (!confirm(`Are you sure you want to change the active status of ${fullName}?`)) return;
      toggleLoading(true);

      try {
        const res = await fetch('/api/admin', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'toggle_block', targetUserId: targetUserId })
        });
        const data = await res.json();
        
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Block operation failed.');
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    async function handleDeleteProductListing(productId, title) {
      if (!confirm(`Are you sure you want to remove listing '${title}'? This will notify the farmer.`)) return;
      toggleLoading(true);

      try {
        const res = await fetch('/api/admin', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete_product', productId: productId })
        });
        const data = await res.json();
        
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Failed to remove listing.');
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    async function handleLogout() {
      toggleLoading(true);
      try {
        const res = await fetch('/api/auth/logout', { method: 'POST' });
        if (res.ok) {
          window.location.href = '/login';
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    function toggleLoading(show) {
      const overlay = document.getElementById('loadingOverlay');
      if (show) {
        overlay.classList.replace('hidden', 'flex');
      } else {
        overlay.classList.replace('flex', 'hidden');
      }
    }
  </script>
</body>
</html>
