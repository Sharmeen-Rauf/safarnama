<?php
require_once __DIR__ . '/../config/db.php';
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
</head>
<body class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white min-height-screen flex flex-col font-sans relative overflow-x-hidden">
  
  <!-- Backdrop Radial Light Effect -->
  <div class="fixed top-[-10%] right-[-10%] w-[50vw] h-[50vw] bg-radial from-emerald-500/10 to-transparent z-[-1] pointer-events-none rounded-full blur-3xl"></div>
  <div class="fixed bottom-[-10%] left-[-10%] w-[40vw] h-[40vw] bg-radial from-amber-500/5 to-transparent z-[-1] pointer-events-none rounded-full blur-3xl"></div>

  <div class="max-w-7xl mx-auto px-6 w-full flex-grow flex flex-col">
    <!-- Header -->
    <header class="flex justify-between items-center py-6 border-b border-white/10 mb-12">
      <a href="index.php" class="flex items-center gap-3 text-2xl font-bold">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-lg shadow-lg shadow-emerald-500/30">🌿</div>
        <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi</span>
      </a>
      <nav class="flex gap-4 items-center">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="<?php echo $_SESSION['user']['role'] === 'Farmer' ? 'farmer-dashboard.php' : 'buyer-dashboard.php'; ?>" class="text-slate-300 hover:text-white font-medium transition duration-200">Dashboard</a>
          <form action="api/logout.php" method="POST" class="inline">
            <button type="submit" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-5 py-2 rounded-xl text-sm font-semibold transition duration-200">Log Out</button>
          </form>
        <?php else: ?>
          <a href="login.php" class="text-slate-300 hover:text-white font-medium transition duration-200">Log In</a>
          <a href="register.php" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-emerald-950/50 transition duration-200">Register</a>
        <?php endif; ?>
      </nav>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow flex flex-col justify-center items-center text-center py-16 max-w-4xl mx-auto">
      <h1 class="text-5xl md:text-6xl font-extrabold leading-tight mb-6">
        Connecting <span class="bg-gradient-to-r from-emerald-400 to-emerald-300 bg-clip-text text-transparent">Farmers</span> Directly with <span class="bg-gradient-to-r from-amber-300 to-amber-500 bg-clip-text text-transparent">Buyers</span>
      </h1>
      <p class="text-lg md:text-xl text-slate-300 mb-10 leading-relaxed max-w-3xl">
        Digital Mandi is a smart agricultural marketplace platform designed to digitize trading. By connecting growers directly with buyers, we remove middleman exploitation, ensure fair pricing, and build transparency into the supply chain.
      </p>
      
      <div class="flex flex-col sm:flex-row justify-center gap-4 w-full sm:w-auto">
        <?php if (isset($_SESSION['user'])): ?>
          <a href="<?php echo $_SESSION['user']['role'] === 'Farmer' ? 'farmer-dashboard.php' : 'buyer-dashboard.php'; ?>" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white px-8 py-3.5 rounded-xl font-semibold shadow-lg shadow-emerald-950/50 transition duration-200 text-center">
            Go to Your Dashboard
          </a>
        <?php else: ?>
          <a href="register.php" class="bg-gradient-to-r from-emerald-600 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white px-8 py-3.5 rounded-xl font-semibold shadow-lg shadow-emerald-950/50 transition duration-200 text-center">
            Get Started Now
          </a>
          <a href="login.php" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-8 py-3.5 rounded-xl font-semibold transition duration-200 text-center">
            Access Your Account
          </a>
        <?php endif; ?>
      </div>
    </main>

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
</body>
</html>
