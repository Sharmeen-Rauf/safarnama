<?php
require_once __DIR__ . '/../config/db.php';

// Session Guard: Buyer Role Check
if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit();
}

if ($_SESSION['user']['role'] !== 'Buyer') {
    header("Location: /farmer-dashboard");
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

// Fetch categories for search filters
$categories = db_query("SELECT * FROM categories") ?: [];

// Auto-Checkout Trigger Check: if redirected from Home page
$buyProductId = isset($_GET['buyProductId']) ? (int)$_GET['buyProductId'] : null;
$autoOrderProduct = null;
if ($buyProductId) {
    $prodRows = db_query("SELECT p.*, u.full_name as farmer_name FROM products p JOIN users u ON p.farmer_id = u.id WHERE p.id = ? AND p.status = 'Available'", [$buyProductId], "i");
    if ($prodRows && count($prodRows) > 0) {
        $autoOrderProduct = $prodRows[0];
    }
}

// Fetch Buyer's purchases history (FR13 / FR11)
$myOrders = db_query(
    "SELECT o.*, p.title as product_title, p.unit, p.price, p.farmer_id, u.full_name as farmer_name, u.mobile_number as farmer_mobile, r.rating, r.feedback 
     FROM orders o 
     JOIN products p ON o.product_id = p.id 
     JOIN users u ON p.farmer_id = u.id 
     LEFT JOIN reviews r ON r.order_id = o.id 
     WHERE o.buyer_id = ? 
     ORDER BY o.id DESC",
    [$userId],
    "i"
) ?: [];

// Calculate stats
$totalSpent = 0.0;
$pendingShipments = 0;
foreach ($myOrders as $o) {
    if ($o['status'] === 'Delivered') {
        $totalSpent += (float)$o['total_price'];
    }
    if ($o['status'] === 'Pending' || $o['status'] === 'Accepted') {
        $pendingShipments++;
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
  <title>Buyer Dashboard - Digital Mandi</title>
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
          <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-amber-600 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-3 border-4 border-white/10 shadow-lg">
            <?php echo htmlspecialchars($initials); ?>
          </div>
          <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($user['full_name']); ?></h3>
          <span class="px-3 py-0.5 bg-amber-950/40 border border-amber-500/30 text-amber-400 text-xs font-semibold rounded-full mt-1 mb-4">Buyer</span>
          
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
              <span class="text-slate-400 block font-semibold uppercase tracking-wider">Address</span>
              <span class="text-slate-200 font-medium leading-relaxed"><?php echo htmlspecialchars($user['address']); ?></span>
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
                  <div class="w-7 h-7 bg-amber-600 rounded-full flex items-center justify-center font-bold text-white uppercase">
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
        <section class="glass rounded-2xl p-6 shadow-2xl">
          <h2 class="text-xl font-bold mb-1">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
          <p class="text-xs text-slate-300 leading-relaxed">
            Browse fresh crops, order directly from local farmers, track shipments in real-time, rate sellers, and chat with farmers directly.
          </p>
          <div class="mt-4">
            <a href="/" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow transition inline-block">
              🛍️ Visit Direct Crop Marketplace
            </a>
          </div>
        </section>

        <!-- Expenditure & Metrics Board (FR13) -->
        <section class="grid grid-cols-3 gap-4">
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">💳</span>
            <span class="text-lg font-bold text-emerald-400">Rs. <?php echo number_format($totalSpent, 2); ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Total Spent Amount</span>
          </div>
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">🛒</span>
            <span class="text-lg font-bold text-white"><?php echo count($myOrders); ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Total Orders Placed</span>
          </div>
          <div class="glass rounded-xl p-4 flex flex-col">
            <span class="text-xl mb-1">🚚</span>
            <span class="text-lg font-bold text-amber-400"><?php echo $pendingShipments; ?></span>
            <span class="text-[10px] text-slate-400 font-medium">Pending Shipments</span>
          </div>
        </section>

        <!-- Purchases History Table (FR13 / FR11 / FR7) -->
        <section class="glass rounded-2xl p-6 shadow-2xl">
          <h3 class="font-bold text-white text-base mb-4">📦 My Purchase History</h3>
          <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-white/10 text-slate-400 font-semibold uppercase">
                  <th class="py-2.5">Order ID</th>
                  <th class="py-2.5">Farmer</th>
                  <th class="py-2.5">Crop Name</th>
                  <th class="py-2.5">Quantity</th>
                  <th class="py-2.5">Total Cost</th>
                  <th class="py-2.5">Payment</th>
                  <th class="py-2.5">Status</th>
                  <th class="py-2.5 text-right">Actions / Reviews</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($myOrders) > 0): ?>
                  <?php foreach ($myOrders as $o): ?>
                    <tr class="border-b border-white/5 last:border-b-0 text-slate-200 hover:bg-white/2 transition">
                      <td class="py-3 font-semibold text-slate-400">#<?php echo $o['id']; ?></td>
                      <td class="py-3">
                        <span class="font-bold block"><?php echo htmlspecialchars($o['farmer_name']); ?></span>
                        <span class="text-[10px] text-slate-500 block"><?php echo htmlspecialchars($o['farmer_mobile']); ?></span>
                      </td>
                      <td class="py-3"><?php echo htmlspecialchars($o['product_title']); ?></td>
                      <td class="py-3"><?php echo htmlspecialchars($o['quantity']); ?> <?php echo htmlspecialchars($o['unit']); ?></td>
                      <td class="py-3 font-bold text-emerald-400">Rs. <?php echo number_format($o['total_price'], 2); ?></td>
                      <td class="py-3"><?php echo htmlspecialchars($o['payment_method']); ?></td>
                      <td class="py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?php 
                          if ($o['status'] === 'Pending') echo 'bg-yellow-950/40 border border-yellow-500/30 text-yellow-400';
                          elseif ($o['status'] === 'Accepted') echo 'bg-blue-950/40 border border-blue-500/30 text-blue-400';
                          elseif ($o['status'] === 'Delivered') echo 'bg-emerald-950/40 border border-emerald-500/30 text-emerald-400';
                          else echo 'bg-red-950/40 border border-red-500/30 text-red-400';
                        ?>">
                          <?php echo htmlspecialchars($o['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 text-right space-x-2">
                        <?php if ($o['status'] === 'Pending'): ?>
                          <button onclick="handleCancelOrder(<?php echo $o['id']; ?>)" class="text-red-400 hover:text-red-300 font-semibold transition">Cancel</button>
                        <?php elseif ($o['status'] === 'Delivered'): ?>
                          <?php if (empty($o['rating'])): ?>
                            <button onclick="openReviewModal(<?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['product_title']); ?>')" class="bg-amber-600 hover:bg-amber-500 text-white px-2 py-1 rounded text-[10px] font-semibold transition">Rate Seller</button>
                          <?php else: ?>
                            <span class="text-slate-400 text-[10px] block">Rated: <?php echo str_repeat('⭐', $o['rating']); ?></span>
                          <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Contact Action (FR7) -->
                        <a href="/chat?partnerId=<?php echo $o['farmer_id']; ?>" class="bg-white/5 hover:bg-white/10 text-slate-300 px-2 py-1 rounded text-[10px] border border-white/10 transition inline-block">Chat</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="py-8 text-center text-slate-500 italic">
                      You have not placed any orders yet. Visit the Marketplace to browse and buy fresh harvests.
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

  <!-- CHECKOUT / ORDER PLACEMENT MODAL (FR5) -->
  <div id="orderModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="glass max-w-md w-full rounded-2xl p-6 shadow-2xl relative">
      <h3 class="text-lg font-bold text-white mb-2">🛍️ Order Checkout Summary</h3>
      <p class="text-xs text-slate-400 mb-4">Review pricing details and enter order quantity.</p>
      
      <form id="orderForm" onsubmit="handlePlaceOrderSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="orderProductId">
        
        <div class="bg-white/5 rounded-xl p-3 border border-white/10 space-y-2">
          <div class="flex justify-between">
            <span class="text-slate-400">Crop Name:</span>
            <span id="orderTitle" class="font-bold text-white">---</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Farmer:</span>
            <span id="orderFarmer" class="font-semibold text-slate-200">---</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Rate:</span>
            <span class="text-emerald-400 font-bold">Rs. <span id="orderPrice">0.00</span> / <span id="orderUnit">kg</span></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Available Stock:</span>
            <span id="orderStock" class="text-slate-300 font-semibold">---</span>
          </div>
        </div>

        <div>
          <label class="block text-slate-400 mb-1 font-medium">Order Quantity *</label>
          <input type="number" step="0.1" id="orderQuantity" required oninput="calculateTotalCost()" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
        </div>

        <div>
          <label class="block text-slate-400 mb-1 font-medium">Payment Option * (FR8)</label>
          <select id="orderPaymentMethod" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition">
            <option value="COD">Cash on Delivery (COD)</option>
            <option value="Digital">Digital Wallet / Card Placeholder</option>
          </select>
        </div>

        <div class="border-t border-dashed border-white/10 pt-4 flex justify-between items-center">
          <span class="text-sm text-slate-300 font-semibold">Total Cost Summary:</span>
          <span class="text-base font-extrabold text-emerald-400">Rs. <span id="orderTotalCost">0.00</span></span>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="toggleOrderModal(false)" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold px-4 py-2 rounded-xl transition">
            Cancel
          </button>
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-5 py-2 rounded-xl shadow transition">
            Confirm Purchase
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- RATINGS & REVIEWS MODAL (FR11) -->
  <div id="reviewModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="glass max-w-md w-full rounded-2xl p-6 shadow-2xl relative">
      <h3 class="text-lg font-bold text-white mb-2">⭐️ Rate Order Transaction</h3>
      <p class="text-xs text-slate-400 mb-4">Help us improve the service by reviewing the farmer.</p>
      
      <form id="reviewForm" onsubmit="handleReviewFormSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="reviewOrderId">
        
        <div>
          <span class="text-slate-400 block mb-1">Crop Title: <strong id="reviewProductTitle" class="text-white">---</strong></span>
        </div>

        <div>
          <label class="block text-slate-400 mb-2 font-medium">Star Rating (1 - 5 stars) *</label>
          <div class="flex gap-3 text-2xl justify-center py-2 bg-slate-950/30 border border-white/5 rounded-xl">
            <button type="button" onclick="setStarRating(1)" class="starBtn opacity-40 hover:opacity-100 transition">⭐</button>
            <button type="button" onclick="setStarRating(2)" class="starBtn opacity-40 hover:opacity-100 transition">⭐</button>
            <button type="button" onclick="setStarRating(3)" class="starBtn opacity-40 hover:opacity-100 transition">⭐</button>
            <button type="button" onclick="setStarRating(4)" class="starBtn opacity-40 hover:opacity-100 transition">⭐</button>
            <button type="button" onclick="setStarRating(5)" class="starBtn opacity-40 hover:opacity-100 transition">⭐</button>
          </div>
          <input type="hidden" id="reviewRating" required value="0">
        </div>

        <div>
          <label class="block text-slate-400 mb-1 font-medium">Feedback Description</label>
          <textarea id="reviewFeedback" rows="3" placeholder="Explain your experience with the farmer, crop freshness..." class="w-full bg-slate-900 border border-white/10 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-emerald-500 transition"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="toggleReviewModal(false)" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white font-semibold px-4 py-2 rounded-xl transition">
            Cancel
          </button>
          <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold px-5 py-2 rounded-xl shadow transition">
            Submit Rating
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="w-10 h-10 border-4 border-white/10 border-t-emerald-500 rounded-full animate-spin"></div>
  </div>

  <script>
    let activePriceRate = 0.0;
    let maxAvailableStock = 0.0;
    
    // Auto-Checkout loader check from PHP variables
    window.addEventListener('DOMContentLoaded', () => {
      const autoCropData = <?php echo $autoOrderProduct ? json_encode($autoOrderProduct) : 'null'; ?>;
      if (autoCropData) {
        openOrderModal(autoCropData);
      }
    });

    function toggleOrderModal(show) {
      const modal = document.getElementById('orderModal');
      const form = document.getElementById('orderForm');
      if (show) {
        modal.classList.replace('hidden', 'flex');
      } else {
        modal.classList.replace('flex', 'hidden');
        form.reset();
        // Remove buyProductId query param from URL so page reloads clean
        const urlWithoutParams = window.location.pathname;
        window.history.replaceState({}, document.title, urlWithoutParams);
      }
    }

    function openOrderModal(crop) {
      document.getElementById('orderProductId').value = crop.id;
      document.getElementById('orderTitle').innerText = crop.title;
      document.getElementById('orderFarmer').innerText = crop.farmer_name;
      document.getElementById('orderPrice').innerText = crop.price.toFixed(2);
      document.getElementById('orderUnit').innerText = crop.unit;
      document.getElementById('orderStock').innerText = `${crop.quantity} ${crop.unit}`;
      
      activePriceRate = crop.price;
      maxAvailableStock = crop.quantity;
      
      calculateTotalCost();
      toggleOrderModal(true);
    }

    function calculateTotalCost() {
      const qtyInput = document.getElementById('orderQuantity').value;
      const qty = parseFloat(qtyInput) || 0;
      const cost = qty * activePriceRate;
      
      document.getElementById('orderTotalCost').innerText = cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function handlePlaceOrderSubmit(e) {
      e.preventDefault();
      
      const qty = parseFloat(document.getElementById('orderQuantity').value);
      if (qty <= 0) {
        alert('Please specify a positive quantity.');
        return;
      }
      if (qty > maxAvailableStock) {
        alert(`Cannot order more than available stock (${maxAvailableStock} left).`);
        return;
      }

      toggleLoading(true);

      const payload = {
        action: 'create',
        productId: parseInt(document.getElementById('orderProductId').value),
        quantity: qty,
        paymentMethod: document.getElementById('orderPaymentMethod').value
      };

      try {
        const res = await fetch('/api/orders', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if (res.ok) {
          alert(data.message || 'Order placed successfully!');
          window.location.href = '/buyer-dashboard';
        } else {
          alert(data.message || 'Order failed to process.');
        }
      } catch (err) {
        console.error(err);
        alert('Network communication error.');
      } finally {
        toggleLoading(false);
      }
    }

    async function handleCancelOrder(orderId) {
      if (!confirm('Are you sure you want to cancel this order?')) return;
      toggleLoading(true);

      try {
        const res = await fetch('/api/orders', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'update_status', orderId: orderId, status: 'Cancelled' })
        });
        const data = await res.json();
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Failed to cancel order.');
        }
      } catch (err) {
        console.error(err);
      } finally {
        toggleLoading(false);
      }
    }

    // Rating Systems Logic
    function toggleReviewModal(show) {
      const modal = document.getElementById('reviewModal');
      const form = document.getElementById('reviewForm');
      if (show) {
        modal.classList.replace('hidden', 'flex');
      } else {
        modal.classList.replace('flex', 'hidden');
        form.reset();
        setStarRating(0);
      }
    }

    function openReviewModal(orderId, cropTitle) {
      document.getElementById('reviewOrderId').value = orderId;
      document.getElementById('reviewProductTitle').innerText = cropTitle;
      toggleReviewModal(true);
    }

    function setStarRating(rating) {
      document.getElementById('reviewRating').value = rating;
      const stars = document.querySelectorAll('.starBtn');
      stars.forEach((star, index) => {
        if (index < rating) {
          star.classList.remove('opacity-40');
          star.classList.add('opacity-100');
        } else {
          star.classList.remove('opacity-100');
          star.classList.add('opacity-40');
        }
      });
    }

    async function handleReviewFormSubmit(e) {
      e.preventDefault();
      const rating = parseInt(document.getElementById('reviewRating').value);
      if (rating < 1 || rating > 5) {
        alert('Please click on stars to select a rating.');
        return;
      }

      toggleLoading(true);
      const payload = {
        action: 'add',
        orderId: parseInt(document.getElementById('reviewOrderId').value),
        rating: rating,
        feedback: document.getElementById('reviewFeedback').value
      };

      try {
        const res = await fetch('/api/reviews', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
          alert(data.message);
          window.location.reload();
        } else {
          alert(data.message || 'Failed to submit review.');
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
