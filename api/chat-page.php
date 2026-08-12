<?php
require_once __DIR__ . '/../config/db.php';

// Session Guard: Verify Auth
if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit();
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Get active partner ID
$partnerId = isset($_GET['partnerId']) ? (int)$_GET['partnerId'] : 0;
$partner = null;

if ($partnerId > 0) {
    // Fetch partner details
    $partnerRows = db_query("SELECT id, full_name, email, mobile_number, role FROM users WHERE id = ?", [$partnerId], "i");
    if ($partnerRows && count($partnerRows) > 0) {
        $partner = $partnerRows[0];
    }
}

// Fetch active contacts (inbox)
$contacts = db_query(
    "SELECT DISTINCT u.id, u.full_name, u.role 
     FROM users u 
     JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id) 
     WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? 
     ORDER BY u.full_name ASC",
    [$userId, $userId, $userId],
    "iii"
) ?: [];

// Find appropriate dashboard link
$dashboardLink = '/buyer-dashboard';
if ($userRole === 'Farmer') {
    $dashboardLink = '/farmer-dashboard';
} elseif ($userRole === 'Admin') {
    $dashboardLink = '/admin-dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat Box - Digital Mandi</title>
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
<body class="bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white h-screen flex flex-col font-sans overflow-hidden">

  <!-- Header -->
  <header class="flex justify-between items-center py-4 px-6 border-b border-white/10 shrink-0">
    <a href="/" class="flex items-center gap-3 text-xl font-bold">
      <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-full flex items-center justify-center text-base shadow-lg">🌿</div>
      <span class="bg-gradient-to-r from-emerald-400 to-amber-300 bg-clip-text text-transparent">Digital Mandi Chat</span>
    </a>
    <div class="flex items-center gap-4">
      <a href="<?php echo $dashboardLink; ?>" class="bg-white/5 border border-white/10 hover:bg-white/10 text-white px-4 py-1.5 rounded-xl text-xs font-semibold transition">
        ← Back to Dashboard
      </a>
    </div>
  </header>

  <!-- Main Chat Workspace (Fill remaining screen height) -->
  <div class="flex-grow flex overflow-hidden w-full max-w-7xl mx-auto px-6 py-6">
    <div class="glass w-full h-full rounded-2xl overflow-hidden grid grid-cols-1 md:grid-cols-[300px_1fr] border border-white/10">
      
      <!-- Left sidebar: Contacts Inbox list -->
      <aside class="border-r border-white/10 flex flex-col overflow-hidden bg-slate-950/20">
        <div class="p-4 border-b border-white/10">
          <h3 class="font-bold text-white text-sm">📥 Conversations Inbox</h3>
        </div>
        <div class="flex-grow overflow-y-auto p-4 space-y-2">
          <?php if (count($contacts) > 0): ?>
            <?php foreach ($contacts as $c): ?>
              <a href="/chat?partnerId=<?php echo $c['id']; ?>" class="flex items-center gap-3 p-3 rounded-xl transition text-xs <?php echo ($partnerId === (int)$c['id']) ? 'bg-emerald-600/30 border border-emerald-500/40' : 'bg-white/2 hover:bg-white/5 border border-transparent'; ?>">
                <div class="w-8 h-8 bg-emerald-700 rounded-full flex items-center justify-center font-bold text-white uppercase">
                  <?php echo substr($c['full_name'], 0, 1); ?>
                </div>
                <div class="flex-grow min-w-0">
                  <span class="font-bold text-white block truncate"><?php echo htmlspecialchars($c['full_name']); ?></span>
                  <span class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($c['role']); ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-slate-500 text-xs italic text-center py-6">No previous conversations.</p>
          <?php endif; ?>
        </div>
      </aside>

      <!-- Right Column: Active Chat Box -->
      <main class="flex flex-col overflow-hidden bg-slate-900/10">
        <?php if ($partner): ?>
          <!-- Active Chat Partner Header (FR7 Share contact details) -->
          <div class="p-4 border-b border-white/10 flex justify-between items-center shrink-0 bg-white/2">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-emerald-700 rounded-full flex items-center justify-center font-bold text-white uppercase text-sm">
                <?php echo substr($partner['full_name'], 0, 1); ?>
              </div>
              <div>
                <h4 class="font-bold text-white text-sm"><?php echo htmlspecialchars($partner['full_name']); ?></h4>
                <span class="px-2 py-0.5 bg-emerald-950/40 border border-emerald-500/20 text-emerald-400 text-[9px] font-semibold rounded-full"><?php echo $partner['role']; ?></span>
              </div>
            </div>
            <!-- Contact Details sharing (FR7) -->
            <div class="text-right text-xs">
              <span class="text-slate-400 block text-[10px] font-medium">CONTACT NUMBER (FR7)</span>
              <a href="tel:<?php echo htmlspecialchars($partner['mobile_number']); ?>" class="font-bold text-emerald-400 hover:underline"><?php echo htmlspecialchars($partner['mobile_number']); ?></a>
            </div>
          </div>

          <!-- Message History scroll area -->
          <div id="chatHistory" class="flex-grow overflow-y-auto p-6 space-y-4">
            <div class="text-center text-slate-500 text-xs py-4">Loading conversation history...</div>
          </div>

          <!-- Bottom Text Input and Send bar -->
          <div class="p-4 border-t border-white/10 shrink-0 bg-white/2">
            <form id="sendMessageForm" onsubmit="handleSendMessageSubmit(event)" class="flex gap-3">
              <input type="text" id="messageInput" required autocomplete="off" placeholder="Write your message here... type to negotiate price or arrange delivery..." class="flex-grow bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 transition">
              <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow transition">
                Send Msg
              </button>
            </form>
          </div>

        <?php else: ?>
          <!-- Empty State (No contact selected) -->
          <div class="flex-grow flex flex-col items-center justify-center text-center p-8">
            <span class="text-4xl mb-3">💬</span>
            <h4 class="font-bold text-white text-base">Select a conversation</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-xs">
              Choose a contact from the inbox list, or browse the marketplace to message a seller.
            </p>
          </div>
        <?php endif; ?>
      </main>

    </div>
  </div>

  <script>
    const partnerId = <?php echo $partnerId; ?>;
    const currentUserId = <?php echo $userId; ?>;
    let pollInterval = null;

    if (partnerId > 0) {
      // Fetch history immediately
      fetchChatHistory();
      
      // Setup AJAX polling to fetch new messages every 3 seconds (FR7 Real-time sync)
      pollInterval = setInterval(fetchChatHistory, 3000);
    }

    async function fetchChatHistory() {
      try {
        const res = await fetch(`/api/chat?partnerId=${partnerId}`);
        if (res.ok) {
          const messages = await res.json();
          renderMessages(messages);
        }
      } catch (err) {
        console.error("Chat polling error:", err);
      }
    }

    function renderMessages(messages) {
      const container = document.getElementById('chatHistory');
      if (messages.length === 0) {
        container.innerHTML = `
          <div class="text-center text-slate-500 text-xs py-8 italic">
            No messages yet. Write a message below to start your conversation!
          </div>`;
        return;
      }

      // Track scroll status before updating content
      const shouldScroll = container.scrollTop + container.clientHeight >= container.scrollHeight - 50;

      let html = '';
      messages.forEach(msg => {
        const isSentByMe = parseInt(msg.sender_id) === currentUserId;
        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        if (isSentByMe) {
          // Sent message bubble (emerald)
          html += `
            <div class="flex justify-end">
              <div class="max-w-[70%] bg-emerald-600/90 text-white rounded-2xl rounded-tr-none px-4 py-2.5 shadow border border-emerald-500/20 text-xs">
                <p class="leading-relaxed break-words">${escapeHTML(msg.message)}</p>
                <span class="text-[9px] text-emerald-200 block text-right mt-1">${time}</span>
              </div>
            </div>`;
        } else {
          // Received message bubble (slate)
          html += `
            <div class="flex justify-start">
              <div class="max-w-[70%] bg-white/5 text-slate-200 rounded-2xl rounded-tl-none px-4 py-2.5 shadow border border-white/5 text-xs">
                <p class="leading-relaxed break-words">${escapeHTML(msg.message)}</p>
                <span class="text-[9px] text-slate-500 block text-left mt-1">${time}</span>
              </div>
            </div>`;
        }
      });

      container.innerHTML = html;

      // Auto scroll to bottom if they were already near bottom
      if (shouldScroll) {
        container.scrollTop = container.scrollHeight;
      }
    }

    async function handleSendMessageSubmit(e) {
      e.preventDefault();
      const input = document.getElementById('messageInput');
      const text = input.value.trim();
      
      if (!text) return;
      input.value = '';

      try {
        const res = await fetch('/api/chat', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'send', receiverId: partnerId, message: text })
        });
        
        if (res.ok) {
          // Reload history immediately
          fetchChatHistory();
          // Scroll immediately to bottom
          const container = document.getElementById('chatHistory');
          setTimeout(() => {
            container.scrollTop = container.scrollHeight;
          }, 100);
        } else {
          alert('Failed to send message.');
        }
      } catch (err) {
        console.error("Message send error:", err);
      }
    }

    function escapeHTML(str) {
      return str.replace(/[&<>'"]/g, 
        tag => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          "'": '&#39;',
          '"': '&quot;'
        }[tag] || tag)
      );
    }
  </script>
</body>
</html>
