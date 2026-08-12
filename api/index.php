<?php
require_once __DIR__ . '/../config/db.php';

// Fetch categories for filter dropdown
$categories = db_query("SELECT * FROM categories") ?: [];

// Get search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCategory = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$city = isset($_GET['city']) ? trim($_GET['city']) : '';

$searchParam = '%' . $search . '%';
$cityParam = '%' . $city . '%';

// Fetch products based on filters
if ($selectedCategory) {
    $products = db_query(
        "SELECT p.*, u.full_name as farmer_name, u.city, u.province, c.name as category_name 
         FROM products p 
         JOIN users u ON p.farmer_id = u.id 
         JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'Available' 
           AND p.category_id = ? 
           AND (p.title LIKE ? OR p.description LIKE ?) 
           AND u.city LIKE ?
         ORDER BY p.id DESC LIMIT 12",
        [$selectedCategory, $searchParam, $searchParam, $cityParam],
        "isss"
    ) ?: [];
} else {
    $products = db_query(
        "SELECT p.*, u.full_name as farmer_name, u.city, u.province, c.name as category_name 
         FROM products p 
         JOIN users u ON p.farmer_id = u.id 
         JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'Available' 
           AND (p.title LIKE ? OR p.description LIKE ?) 
           AND u.city LIKE ?
         ORDER BY p.id DESC LIMIT 12",
        [$searchParam, $searchParam, $cityParam],
        "sss"
    ) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Digital Mandi – Smart Agricultural Marketplace Platform</title>
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
  
  <!-- Backdrop Light Effects -->
  <div class="fixed top-[-10%] right-[-10%] w-[50vw] h-[50vw] bg-radial from-emerald-500/10 to-transparent z-[-1] pointer-events-none rounded-full blur-3xl"></div>
  <div class="fixed bottom-[-10%] left-[-10%] w-[40vw] h-[40vw] bg-radial from-amber-500/5 to-transparent z-[-1] pointer-events-none rounded-full blur-3xl"></div>

  <div class="max-w-7xl mx-auto px-6 w-full flex-grow flex flex-col">
    <!-- Header -->
    <header class="flex justify-between items-center py-6 border-b border-white/10 mb-12">
      <a href="/" class="flex items-center gap-3 text-2xl font-bold">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-lg shadow-lg shadow-emerald-500/30">🌿</div>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <nav class="flex gap-4 items-center">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="<?php 
            if ($_SESSION['user']['role'] === 'Farmer') echo '/farmer-dashboard';
            elseif ($_SESSION['user']['role'] === 'Admin') echo '/admin-dashboard';
            else echo '/buyer-dashboard'; 
          ?>" class="text-slate-300 hover:text-white font-medium transition duration-200">Dashboard</a>
          <button onclick="handleLogout()" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-5 py-2 rounded-xl text-sm font-semibold transition duration-200">Log Out</button>
        <?php else: ?>
          <a href="/login" class="text-slate-300 hover:text-white font-medium transition duration-200">Log In</a>
          <a href="/register" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-emerald-950/50 transition duration-200">Register</a>
        <?php endif; ?>
      </nav>
    </header>

    <!-- Hero Section -->
    <main class="text-center py-8 max-w-4xl mx-auto mb-16">
      <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6">
        Connecting <span class="bg-gradient-to-r from-emerald-400 to-emerald-300 bg-clip-text text-transparent">Farmers</span> Directly with <span class="bg-gradient-to-r from-amber-300 to-amber-500 bg-clip-text text-transparent">Buyers</span>
      </h1>
      <p class="text-lg text-slate-300 mb-8 leading-relaxed max-w-3xl">
        Digital Mandi is a smart agricultural marketplace platform designed to digitize trading. By connecting growers directly with buyers, we remove middleman exploitation, ensure fair pricing, and build transparency into the supply chain.
      </p>
    </main>

    <!-- Live Marketplace Search & Listings (FR4 / FR12) -->
    <section class="mb-16">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">🌾 Direct Crop Marketplace</h2>
        <span class="text-sm text-slate-400"><?php echo count($products); ?> crops available</span>
      </div>

      <!-- Search Filters Bar -->
      <form method="GET" action="/" class="glass rounded-2xl p-4 mb-8 grid md:grid-cols-4 gap-4">
        <!-- Keyword Search -->
        <div>
          <label class="block text-xs text-slate-400 mb-1 font-medium">Search Crops</label>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="e.g. Wheat, Rice, Potato..." class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>
        <!-- Category Filter -->
        <div>
          <label class="block text-xs text-slate-400 mb-1 font-medium">Category</label>
          <select name="category" class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 transition">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php if ($selectedCategory === (int)$cat['id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Location-Based Search (FR12) -->
        <div>
          <label class="block text-xs text-slate-400 mb-1 font-medium">Location / City</label>
          <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="e.g. Okara, Lahore..." class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>
        <!-- Submit Button -->
        <div class="flex items-end">
          <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl py-2 text-sm font-semibold transition shadow-md shadow-emerald-950/30">
            Apply Filters
          </button>
        </div>
      </form>

      <!-- Crop Listings Grid (FR3 / FR4) -->
      <?php if (count($products) > 0): ?>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <?php foreach ($products as $p): ?>
            <div class="glass rounded-2xl p-4 flex flex-col justify-between hover:border-emerald-500/30 transition duration-300 relative group">
              <!-- Crop Image (FR3) -->
              <div class="w-full h-40 bg-slate-900 rounded-xl overflow-hidden mb-4 relative flex items-center justify-center border border-white/5">
                <?php if (!empty($p['image_url'])): ?>
                  <img src="<?php echo $p['image_url']; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                  <span class="text-4xl">🌾</span>
                <?php endif; ?>
                <span class="absolute top-2 right-2 bg-emerald-500/25 border border-emerald-500/50 text-emerald-400 text-xs px-2 py-0.5 rounded-full font-medium">
                  <?php echo htmlspecialchars($p['category_name']); ?>
                </span>
              </div>
              
              <!-- Crop Meta -->
              <div>
                <h3 class="font-bold text-lg text-white mb-1 group-hover:text-emerald-400 transition">
                  <?php echo htmlspecialchars($p['title']); ?>
                </h3>
                <p class="text-xs text-slate-400 mb-3 line-clamp-2">
                  <?php echo htmlspecialchars($p['description'] ?: 'Fresh harvest directly from the farm.'); ?>
                </p>
                <div class="flex justify-between items-center mb-4 text-sm">
                  <div>
                    <span class="text-xs text-slate-500 block">Price</span>
                    <span class="font-bold text-emerald-400">Rs. <?php echo number_format($p['price'], 2); ?></span>
                    <span class="text-xs text-slate-400">/ <?php echo htmlspecialchars($p['unit']); ?></span>
                  </div>
                  <div class="text-right">
                    <span class="text-xs text-slate-500 block">Stock Available</span>
                    <span class="font-semibold text-slate-200"><?php echo number_format($p['quantity'], 1); ?> <?php echo htmlspecialchars($p['unit']); ?></span>
                  </div>
                </div>
              </div>

              <!-- Footer with Farmer & Action Details -->
              <div class="border-t border-white/5 pt-3 flex justify-between items-center text-xs">
                <div>
                  <span class="text-slate-400 block font-medium">📍 <?php echo htmlspecialchars($p['city']); ?></span>
                  <span class="text-slate-500 block font-light">By <?php echo htmlspecialchars($p['farmer_name']); ?></span>
                </div>
                <div>
                  <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($_SESSION['user']['role'] === 'Buyer'): ?>
                      <a href="/buyer-dashboard?buyProductId=<?php echo $p['id']; ?>" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-3 py-1.5 rounded-lg transition">
                        Order Now
                      </a>
                    <?php else: ?>
                      <span class="text-slate-500 italic">Farmer View</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <a href="/login" class="bg-white/5 border border-white/10 hover:bg-white/10 text-slate-300 px-3 py-1.5 rounded-lg transition">
                      Log In to Buy
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="glass rounded-2xl p-12 text-center text-slate-400">
          <span class="text-4xl block mb-3">🌾</span>
          <p class="font-medium text-lg text-slate-300">No crop listings found.</p>
          <p class="text-sm text-slate-500 mt-1">Try adjusting your search criteria or checking back later.</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Highlights Section -->
    <section class="grid md:grid-cols-2 gap-8 my-12">
      <!-- Farmer Card -->
      <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-8 backdrop-blur-xl shadow-2xl hover:border-emerald-500/20 transition duration-300">
        <div class="text-4xl mb-4">🚜</div>
        <h3 class="text-xl font-bold mb-3 text-emerald-400">For Farmers</h3>
        <p class="text-slate-300 leading-relaxed text-sm md:text-base">
          List your harvest, set your base price per crop, upload product images, and receive secure, direct purchase requests from verified wholesale and retail buyers across the country.
        </p>
      </div>

      <!-- Buyer Card -->
      <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-8 backdrop-blur-xl shadow-2xl hover:border-amber-500/20 transition duration-300">
        <div class="text-4xl mb-4">🌾</div>
        <h3 class="text-xl font-bold mb-3 text-amber-400">For Buyers</h3>
        <p class="text-slate-300 leading-relaxed text-sm md:text-base">
          Search crops by location and categories, browse direct listings, review farm details, negotiate pricing without intermediaries, and track delivery status securely.
        </p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="text-center py-8 border-t border-white/10 mt-12 text-slate-500 text-sm">
      <p>&copy; 2026 Digital Mandi. Empowering the Agricultural Supply Chain in Pakistan.</p>
    </footer>
  </div>

  <script>
    async function handleLogout() {
      try {
        const response = await fetch('/api/auth/logout', { method: 'POST' });
        if (response.ok) {
          window.location.href = '/login';
        } else {
          alert('Failed to log out.');
        }
      } catch (err) {
        console.error(err);
      }
    }
  </script>
</body>
</html>
