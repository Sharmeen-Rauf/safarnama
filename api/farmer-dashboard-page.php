<?php
require_once __DIR__ . '/../config/db.php';

// Session Guard: Farmer Role Check
if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit();
}

if ($_SESSION['user']['role'] !== 'Farmer') {
    header("Location: /buyer-dashboard");
    exit();
}

$userId = $_SESSION['user']['id'];

// Fetch user profile info
$userRows = db_query("SELECT full_name, email, mobile_number, address, city, province FROM users WHERE id = ?", [$userId], "i");
if (!$userRows || count($userRows) === 0) {
    header("Location: /login");
    exit();
}
$user = $userRows[0];

// Fetch user farm details
$farmRows = db_query("SELECT farm_name, farm_location, crop_type FROM farms WHERE user_id = ?", [$userId], "i");
$farm = ($farmRows && count($farmRows) > 0) ? $farmRows[0] : [
    "farm_name" => "Not Configured",
    "farm_location" => "Not Configured",
    "crop_type" => "Not Configured"
];

// Fetch categories for adding/editing crop forms
$categories = db_query("SELECT * FROM categories") ?: [];

// Fetch Farmer's crop listings
$myCrops = db_query(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     JOIN categories c ON p.category_id = c.id 
     WHERE p.farmer_id = ? 
     ORDER BY p.id DESC", 
    [$userId], 
    "i"
) ?: [];

// Fetch incoming buyer orders
$incomingOrders = db_query(
    "SELECT o.*, p.title as product_title, p.unit, p.price, u.full_name as buyer_name, u.mobile_number, u.address, u.city, u.province 
     FROM orders o 
     JOIN products p ON o.product_id = p.id 
     JOIN users u ON o.buyer_id = u.id 
     WHERE p.farmer_id = ? 
     ORDER BY o.id DESC",
    [$userId],
    "i"
) ?: [];

// Calculate stats
$totalRevenue = 0.0;
$pendingOrdersCount = 0;
foreach ($incomingOrders as $order) {
    if ($order['status'] === 'Delivered') {
        $totalRevenue += (float)$order['total_price'];
    }
    if ($order['status'] === 'Pending') {
        $pendingOrdersCount++;
    }
}

// Fetch recent notifications
$notifications = db_query("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$userId], "i") ?: [];

// Fetch active chat contacts
$activeChats = db_query(
    "SELECT DISTINCT u.id, u.full_name, u.role 
     FROM users u 
     JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id) 
     WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? 
     LIMIT 5",
    [$userId, $userId, $userId],
    "iii"
) ?: [];

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
        <span class="hidden md:inline text-sm text-slate-300">Welcome back, <strong class="text-white"><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
        <button onclick="handleLogout()" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-4 py-2 rounded-xl text-sm font-semibold transition duration-200">Log Out</button>
      </div>
    </header>

    <!-- Dashboard Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-8 flex-grow">
      
      <!-- Profile & Communications Sidebar (Left) -->
      <aside class="space-y-6">
        <!-- Profile Card -->
        <div class="glass rounded-2xl p-6 shadow-2xl flex flex-col items-center text-center">
          <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-3 border-4 border-white/10 shadow-lg">
            <?php echo htmlspecialchars($initials); ?>
          </div>
          <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($user['full_name']); ?></h3>
          <span class="px-3 py-0.5 bg-emerald-950/40 border border-emerald-500/30 text-emerald-400 text-xs font-semibold rounded-full mt-1 mb-4">Farmer</span>
          
          <div class="w-full text-left space-y-3 border-t border-white/10 pt-4 text-xs">
            <div>
              <span class="text-slate-400 block font-semibold uppercase tracking-wider">Mobile Number</span>
              <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($user['mobile_number']); ?></span>
            </div>
            <div>
              <span class="text-slate-400 block font-semibold uppercase tracking-wider">Location</span>
              <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($user['city'] . ', ' . $user['province']); ?></span>
            </div>
            <div>
              <span class="text-slate-400 block font-semibold uppercase tracking-wider font-bold text-emerald-400 mt-2">Farm Name</span>
              <span class="text-slate-200 font-medium"><?php echo htmlspecialchars($farm['farm_name']); ?></span>
            </div>
          </div>
          <a href="/edit-profile" class="w-full bg-white/5 hover:bg-white/10 text-white font-semibold py-2 rounded-xl border border-white/10 transition text-center text-xs mt-4">
            ✏️ Edit Profile
          </a>
        </div>

        <!-- Chats Inbox Widget (FR7) -->
        <div class="glass rounded-2xl p-5 shadow-2xl">
          <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-3">💬 Recent Chats</h4>
          <?php if (count($activeChats) > 0): ?>
            <div class="space-y-2">
              <?php foreach ($activeChats as $chat): ?>
                <a href="/chat?partnerId=<?php echo $chat['id']; ?>" class="flex items-center gap-3 p-2 bg-white/5 hover:bg-white/10 rounded-xl transition text-xs">
                  <div class="w-7 h-7 bg-emerald-700 rounded-full flex items-center justify-center font-bold text-white uppercase">
                    <?php echo substr($chat['full_name'], 0, 1); ?>
                  </div>
                  <div class="flex-grow">
                    <span class="font-bold text-white block"><?php echo htmlspecialchars($chat['full_name']); ?></span>
                    <span class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($chat['role']); ?></span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-slate-500 text-xs italic">No active conversations.</p>
          <?php endif; ?>
        </div>

        <!-- System Notifications Widget (FR9) -->
        <div class="glass rounded-2xl p-5 shadow-2xl">
          <div class="flex justify-between items-center mb-3">
            <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider">🔔 Alerts</h4>
            <?php if (count($notifications) > 0): ?>
              <button onclick="markAllNotificationsRead()" class="text-[10px] text-slate-400 hover:text-white transition">Clear All</button>
            <?php endif; ?>
          </div>
          <?php if (count($notifications) > 0): ?>
            <div class="space-y-3">
              <?php foreach ($notifications as $n): ?>
                <div class="p-2.5 rounded-xl border <?php echo $n['is_read'] ? 'bg-white/2 border-white/5 text-slate-400' : 'bg-emerald-950/20 border-emerald-500/20 text-slate-200'; ?> text-[11px] leading-relaxed">
                  <strong class="block font-bold text-white"><?php echo htmlspecialchars($n['title']); ?></strong>
                  <?php echo htmlspecialchars($n['message']); ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-slate-500 text-xs italic">No new notifications.</p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Dashboard Main Workspace (Right) -->
      <main class="space-y-6">
        
        <!-- Welcome Banner -->
        <section class="glass rounded-2xl p-6 shadow-2xl relative overflow-hidden">
          <h2 class="text-xl font-bold mb-1">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
          <p class="text-xs text-slate-300 leading-relaxed">
            Manage your harvest listings, coordinate incoming buyer requests directly without middlemen, check notifications, and keep track of your earned crop revenue.
          </p>
        </section>

        <!-- Earnings & Metrics Board (FR13) -->
        <section class="grid grid-cols-3 gap-4">
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">💰</span>
            <span class="text-lg font-bold text-emerald-400">Rs. <?php echo number_format($totalRevenue, 2); ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Total Earned Revenue</span>
          </div>
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">📦</span>
            <span class="text-lg font-bold text-white"><?php echo count($myCrops); ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Total Crop Listings</span>
          </div>
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">⚖️</span>
            <span class="text-lg font-bold text-amber-400"><?php echo $pendingOrdersCount; ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Pending Requests</span>
          </div>
        </section>

        <!-- Crop Produce Listings Management (FR3 CRUD) -->
        <section class="glass rounded-2xl p-6 shadow-2xl">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-white text-base">🌾 My Crop Listings</h3>
            <button onclick="toggleAddCropModal(true)" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow transition">
              + Add Produce
            </button>
          </div>
          <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-white/10 text-slate-400 font-semibold uppercase">
                  <th class="py-2.5">Image</th>
                  <th class="py-2.5">Crop Name</th>
                  <th class="py-2.5">Category</th>
                  <th class="py-2.5">Price / Unit</th>
                  <th class="py-2.5">Stock</th>
                  <th class="py-2.5">Status</th>
                  <th class="py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($myCrops) > 0): ?>
                  <?php foreach ($myCrops as $crop): ?>
                    <tr class="border-b border-white/5 last:border-b-0 text-slate-200 hover:bg-white/2 transition">
                      <td class="py-3">
                        <div class="w-10 h-10 bg-slate-900 rounded-lg flex items-center justify-center border border-white/5 overflow-hidden">
                          <?php if (!empty($crop['image_url'])): ?>
                            <img src="<?php echo $crop['image_url']; ?>" alt="" class="w-full h-full object-cover">
                          <?php else: ?>
                            🌾
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="py-3 font-bold"><?php echo htmlspecialchars($crop['title']); ?></td>
                      <td class="py-3 text-slate-400"><?php echo htmlspecialchars($crop['category_name']); ?></td>
                      <td class="py-3 text-emerald-400 font-medium">Rs. <?php echo number_format($crop['price'], 2); ?> / <?php echo htmlspecialchars($crop['unit']); ?></td>
                      <td class="py-3"><?php echo htmlspecialchars($crop['quantity']); ?> <?php echo htmlspecialchars($crop['unit']); ?></td>
                      <td class="py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?php echo $crop['status'] === 'Available' ? 'bg-emerald-950/40 border border-emerald-500/30 text-emerald-400' : 'bg-slate-950/40 border border-slate-500/30 text-slate-400'; ?>">
                          <?php echo htmlspecialchars($crop['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 text-right space-x-2">
                        <button onclick='openEditCropModal(<?php echo json_encode($crop); ?>)' class="text-amber-400 hover:text-amber-300 font-semibold transition">Edit</button>
                        <button onclick="handleDeleteCrop(<?php echo $crop['id']; ?>)" class="text-red-400 hover:text-red-300 font-semibold transition">Delete</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="py-8 text-center text-slate-500 italic">
                      You have not listed any crops yet. Click "+ Add Produce" to list your harvest.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Incoming Buyer Orders Board (FR6 / FR8) -->
        <section class="glass rounded-2xl p-6 shadow-2xl">
          <h3 class="font-bold text-white text-base mb-4">📥 Incoming Buyer Orders</h3>
          <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-white/10 text-slate-400 font-semibold uppercase">
                  <th class="py-2.5">ID</th>
                  <th class="py-2.5">Buyer</th>
                  <th class="py-2.5">Crop Ordered</th>
                  <th class="py-2.5">Quantity</th>
                  <th class="py-2.5">Total cost</th>
                  <th class="py-2.5">Payment</th>
                  <th class="py-2.5">Status</th>
                  <th class="py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($incomingOrders) > 0): ?>
                  <?php foreach ($incomingOrders as $order): ?>
                    <tr class="border-b border-white/5 last:border-b-0 text-slate-200 hover:bg-white/2 transition">
                      <td class="py-3 font-semibold text-slate-400">#<?php echo $order['id']; ?></td>
                      <td class="py-3">
                        <span class="font-bold block"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                        <span class="text-[10px] text-slate-500 block"><?php echo htmlspecialchars($order['mobile_number']); ?></span>
                      </td>
                      <td class="py-3"><?php echo htmlspecialchars($order['product_title']); ?></td>
                      <td class="py-3"><?php echo htmlspecialchars($order['quantity']); ?> <?php echo htmlspecialchars($order['unit']); ?></td>
                      <td class="py-3 font-bold text-emerald-400">Rs. <?php echo number_format($order['total_price'], 2); ?></td>
                      <td class="py-3"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                      <td class="py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?php 
                          if ($order['status'] === 'Pending') echo 'bg-yellow-950/40 border border-yellow-500/30 text-yellow-400';
                          elseif ($order['status'] === 'Accepted') echo 'bg-blue-950/40 border border-blue-500/30 text-blue-400';
                          elseif ($order['status'] === 'Delivered') echo 'bg-emerald-950/40 border border-emerald-500/30 text-emerald-400';
                          else echo 'bg-red-950/40 border border-red-500/30 text-red-400';
                        ?>">
                          <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 text-right space-x-1.5">
                        <?php if ($order['status'] === 'Pending'): ?>
                          <button onclick="handleUpdateOrderStatus(<?php echo $order['id']; ?>, 'Accepted')" class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-1 rounded text-[10px] font-bold transition">Accept</button>
                          <button onclick="handleUpdateOrderStatus(<?php echo $order['id']; ?>, 'Rejected')" class="bg-red-950/40 border border-red-500/30 text-red-400 hover:bg-red-900/50 px-2 py-1 rounded text-[10px] font-bold transition">Reject</button>
                        <?php elseif ($order['status'] === 'Accepted'): ?>
                          <button onclick="handleUpdateOrderStatus(<?php echo $order['id']; ?>, 'Delivered')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-2 py-1 rounded text-[10px] font-bold transition">Deliver</button>
                        <?php else: ?>
                          <span class="text-slate-500 text-[10px] italic">Completed</span>
                        <?php endif; ?>
                        
                        <!-- Contact Action (FR7) -->
                        <a href="/chat?partnerId=<?php echo $order['buyer_id']; ?>" class="bg-white/5 hover:bg-white/10 text-white px-2.5 py-1 rounded text-[10px] font-bold border border-white/10 transition inline-block">Chat</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="py-8 text-center text-slate-500 italic">
                      No incoming purchase requests found.
                    </td>
                  </tr>
                <?php endif; ?>
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

  <!-- ADD / EDIT CROP MODAL -->
  <div id="cropModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="glass max-w-lg w-full rounded-2xl p-6 shadow-2xl relative">
      <h3 id="modalTitle" class="text-lg font-bold text-white mb-4">🌾 Add New Produce</h3>
      
      <form id="cropForm" onsubmit="handleCropFormSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="formCropId">
        
        <div>
          <label class="block text-slate-400 mb-1 font-medium">Crop Title *</label>
          <input type="text" id="formTitle" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-slate-400 mb-1 font-medium">Category *</label>
            <select id="formCategory" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
              <option value="">Select Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-slate-400 mb-1 font-medium">Selling Unit *</label>
            <select id="formUnit" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
              <option value="kg">kg (Kilogram)</option>
              <option value="maund">maund (40 kg)</option>
              <option value="ton">ton (1000 kg)</option>
              <option value="piece">piece</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-slate-400 mb-1 font-medium">Price per Unit (Rs.) *</label>
            <input type="number" step="0.01" id="formPrice" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
          </div>
          <div>
            <label class="block text-slate-400 mb-1 font-medium">Stock Quantity *</label>
            <input type="number" step="0.1" id="formQuantity" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
          </div>
        </div>

        <div>
          <label class="block text-slate-400 mb-1 font-medium">Description</label>
          <textarea id="formDescription" rows="2" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition"></textarea>
        </div>

        <div>
          <label class="block text-slate-400 mb-1 font-medium">Crop Image Upload (Will encode to Base64)</label>
          <input type="file" id="formImageFile" accept="image/*" class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-950/40 file:text-emerald-400 hover:file:bg-emerald-900/50">
          <input type="hidden" id="formImageBase64">
        </div>

        <div id="formStatusContainer" class="hidden">
          <label class="block text-slate-400 mb-1 font-medium">Listing Status *</label>
          <select id="formStatus" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
            <option value="Available">Available</option>
            <option value="Sold Out">Sold Out</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="toggleAddCropModal(false)" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold px-4 py-2 rounded-xl transition">
            Cancel
          </button>
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-5 py-2 rounded-xl shadow transition">
            Save Listing
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Global Loading Spinner -->
  <div id="loadingOverlay" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-10 h-10 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    // Local memory variables
    let isEditing = false;
    let base64ImageString = '';

    // Handle Image file input convert to Base64
    document.getElementById('formImageFile').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          base64ImageString = event.target.result;
          document.getElementById('formImageBase64').value = base64ImageString;
        };
        reader.readAsDataURL(file);
      }
    });

    function toggleAddCropModal(show) {
      const modal = document.getElementById('cropModal');
      const form = document.getElementById('cropForm');
      if (show) {
        if (!isEditing) {
          form.reset();
          document.getElementById('formCropId').value = '';
          document.getElementById('formImageBase64').value = '';
          base64ImageString = '';
          document.getElementById('modalTitle').innerText = '🌾 Add New Produce';
          document.getElementById('formStatusContainer').classList.add('hidden');
        }
        modal.classList.replace('hidden', 'flex');
      } else {
        modal.classList.replace('flex', 'hidden');
        isEditing = false;
      }
    }

    function openEditCropModal(crop) {
      isEditing = true;
      document.getElementById('formCropId').value = crop.id;
      document.getElementById('formTitle').value = crop.title;
      document.getElementById('formCategory').value = crop.category_id;
      document.getElementById('formUnit').value = crop.unit;
      document.getElementById('formPrice').value = crop.price;
      document.getElementById('formQuantity').value = crop.quantity;
      document.getElementById('formDescription').value = crop.description || '';
      document.getElementById('formStatus').value = crop.status;
      document.getElementById('formImageBase64').value = crop.image_url || '';
      base64ImageString = crop.image_url || '';
      
      document.getElementById('modalTitle').innerText = '✏️ Edit Crop Listing';
      document.getElementById('formStatusContainer').classList.remove('hidden');
      toggleAddCropModal(true);
    }

    async function handleCropFormSubmit(e) {
      e.preventDefault();
      toggleLoading(true);

      const cropId = document.getElementById('formCropId').value;
      const payload = {
        action: isEditing ? 'edit' : 'add',
        title: document.getElementById('formTitle').value,
        categoryId: parseInt(document.getElementById('formCategory').value),
        unit: document.getElementById('formUnit').value,
        price: parseFloat(document.getElementById('formPrice').value),
        quantity: parseFloat(document.getElementById('formQuantity').value),
        description: document.getElementById('formDescription').value,
        image: document.getElementById('formImageBase64').value,
      };

      if (isEditing) {
        payload.id = parseInt(cropId);
        payload.status = document.getElementById('formStatus').value;
      }

      try {
        const res = await fetch('/api/products', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Operation failed');
        }
      } catch (err) {
        console.error(err);
        alert('Server network error occurred.');
      } finally {
        toggleLoading(false);
      }
    }

    async function handleDeleteCrop(id) {
      if (!confirm('Are you sure you want to delete this listing permanently?')) return;
      toggleLoading(true);

      try {
        const res = await fetch('/api/products', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: id })
        });
        const data = await res.json();
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Deletion failed');
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    async function handleUpdateOrderStatus(orderId, status) {
      if (!confirm(`Are you sure you want to mark this order request as ${status}?`)) return;
      toggleLoading(true);

      try {
        const res = await fetch('/api/orders', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'update_status', orderId: orderId, status: status })
        });
        const data = await res.json();
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Failed to update order status.');
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    async function markAllNotificationsRead() {
      try {
        const res = await fetch('/api/notifications', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'read_all' })
        });
        if (res.ok) {
          window.location.reload();
        }
      } catch (err) {
        console.error(err);
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
