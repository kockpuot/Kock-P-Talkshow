<?php
// shows.php - Display all shows (with optional approval status)
session_start(); // MUST be at the very top
require_once 'database/kock_p_talkshow.php';

// Check if 'status' column exists in home_shows
try {
    $pdo->query("SELECT status FROM home_shows LIMIT 1");
    $hasStatus = true;
} catch (PDOException $e) {
    $hasStatus = false;
    // Optionally add the column automatically (uncomment if you want)
    // $pdo->exec("ALTER TABLE home_shows ADD COLUMN status ENUM('pending','approved','rejected') DEFAULT 'approved'");
    // $hasStatus = true;
}

// Fetch shows: if status column exists, only approved; otherwise all
if ($hasStatus) {
    $stmt = $pdo->query("SELECT * FROM home_shows WHERE status = 'approved' ORDER BY display_order ASC, created_at DESC");
} else {
    $stmt = $pdo->query("SELECT * FROM home_shows ORDER BY display_order ASC, created_at DESC");
}
$allShows = $stmt->fetchAll();

// Get unique categories for filter
$categories = array_unique(array_column($allShows, 'category'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Shows – Kock P Talkshow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (keep all existing CSS from previous version - same as before) */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', sans-serif; background:#f9f9f9; color:#222; }
        .container { max-width:1300px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; position:sticky; top:0; z-index:100; }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { display:flex; align-items:center; gap:10px; font-size:1.8rem; font-weight:bold; }
        .logo img { height:50px; width:auto; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-links a:hover, .nav-links a.active { color:#e94560; }
        .page-title { text-align:center; margin:2rem 0 1rem; font-size:2.5rem; }
        .filter-bar { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem; margin:2rem 0; background:white; padding:1rem 1.5rem; border-radius:50px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .filter-categories { display:flex; gap:0.8rem; flex-wrap:wrap; }
        .filter-btn { background:#eee; border:none; padding:8px 20px; border-radius:30px; cursor:pointer; font-weight:500; transition:0.2s; }
        .filter-btn.active, .filter-btn:hover { background:#e94560; color:white; }
        .search-box { display:flex; gap:8px; }
        .search-box input { padding:8px 15px; border-radius:30px; border:1px solid #ddd; width:220px; }
        .search-box button { background:#1a1a2e; color:white; border:none; padding:0 18px; border-radius:30px; cursor:pointer; }
        .shows-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px,1fr)); gap:2rem; margin:2rem 0; }
        .show-card { background:white; border-radius:20px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.08); transition:0.3s; }
        .show-card:hover { transform:translateY(-5px); box-shadow:0 15px 30px rgba(0,0,0,0.15); }
        .media-wrapper { position:relative; background:#000; aspect-ratio:16/9; }
        .media-wrapper iframe { width:100%; height:100%; border:none; }
        .card-badge { position:absolute; top:12px; left:12px; background:#e94560; color:white; padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:bold; }
        .card-content { padding:1.2rem; }
        .card-title { font-size:1.2rem; margin-bottom:0.5rem; }
        .card-meta { display:flex; justify-content:space-between; margin:0.8rem 0; font-size:0.85rem; color:#666; }
        .action-buttons { display:flex; gap:1rem; margin-top:0.8rem; border-top:1px solid #eee; padding-top:0.8rem; }
        .action-btn { background:none; border:none; cursor:pointer; font-size:0.9rem; color:#555; display:inline-flex; align-items:center; gap:5px; }
        .action-btn:hover { color:#e94560; }
        .no-results { text-align:center; padding:3rem; font-size:1.2rem; color:#777; }
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; display:inline-block; }
        .social-links a:hover { color:#e94560; }
        @media (max-width:768px) { .filter-bar { flex-direction:column; align-items:stretch; border-radius:20px; } .search-box input { width:100%; } .nav-links { margin-top:1rem; justify-content:center; width:100%; } .logo { font-size:1.2rem; } .logo img { height:35px; } }
    </style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo"><img src="images/logo.png" alt="Logo"> Kock P <span>Talkshow</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php" class="active">Shows</a></li>
                <li><a href="request_talk.php">Request Talk</a></li>
                <li><a href="share_history.php">Share Story</a></li>
                <li><a href="advertise.php">Advertise</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <?php
                if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
                    echo '<li><a href="my_account.php">My Account</a></li>';
                    echo '<li><a href="logout.php">Logout</a></li>';
                } else {
                    echo '<li><a href="login.php">Login</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <h1 class="page-title">🎬 All Shows</h1>
    <div class="filter-bar">
        <div class="filter-categories" id="categoryFilters">
            <button class="filter-btn active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-category="<?php echo htmlspecialchars($cat); ?>"><?php echo ucfirst($cat); ?></button>
            <?php endforeach; ?>
        </div>
        <div class="search-box"><input type="text" id="searchInput" placeholder="Search shows..."><button id="searchBtn"><i class="fas fa-search"></i></button></div>
    </div>
    <div id="showsGrid" class="shows-grid"></div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Real stories, real voices.</p>
        <div class="social-links">
            <a href="https://facebook.com/kockp01"><i class="fab fa-facebook"></i></a>
            <a href="https://youtube.com/@kockp01"><i class="fab fa-youtube"></i></a>
            <a href="https://instagram.com/kockp01"><i class="fab fa-instagram"></i></a>
            <a href="https://tiktok.com/@kockp01"><i class="fab fa-tiktok"></i></a>
            <a href="https://wa.me/251993557112"><i class="fab fa-whatsapp"></i></a>
            <a href="https://t.me/kockpuot"><i class="fab fa-telegram"></i></a>
            <a href="mailto:kockpuot@gmail.com"><i class="fas fa-envelope"></i></a>
        </div>
        <p>Email: kockpuot@gmail.com | WhatsApp: +251993557112 | Telegram: @kockpuot</p>
    </div>
</footer>

<script>
    const showsData = <?php echo json_encode($allShows); ?>;
    function renderShows(filterCategory = "all", searchTerm = "") {
        const grid = document.getElementById("showsGrid");
        let filtered = showsData.filter(show => {
            if (filterCategory !== "all" && show.category !== filterCategory) return false;
            if (searchTerm && !show.title.toLowerCase().includes(searchTerm.toLowerCase()) && !show.description.toLowerCase().includes(searchTerm.toLowerCase())) return false;
            return true;
        });
        if (filtered.length === 0) { grid.innerHTML = `<div class="no-results"><i class="fas fa-microphone-alt"></i> No shows found.</div>`; return; }
        grid.innerHTML = filtered.map(show => `
            <div class="show-card">
                <div class="media-wrapper"><div class="card-badge">${show.category}</div><iframe src="${show.embed_url}" title="${show.title.replace(/"/g, '&quot;')}" frameborder="0" allowfullscreen></iframe></div>
                <div class="card-content">
                    <h3 class="card-title">${show.title}</h3>
                    <p>${show.description.substring(0, 120)}${show.description.length > 120 ? '...' : ''}</p>
                    <div class="card-meta"><span><i class="far fa-calendar-alt"></i> ${new Date(show.created_at).toLocaleDateString()}</span></div>
                    <div class="action-buttons">
                        <button class="action-btn share-btn" data-title="${show.title.replace(/"/g, '&quot;')}" data-url="${window.location.href}?show=${show.id}"><i class="fas fa-share-alt"></i> Share</button>
                    </div>
                </div>
            </div>
        `).join("");
        document.querySelectorAll(".share-btn").forEach(btn => {
            btn.addEventListener("click", function() { const title = this.getAttribute("data-title"), url = this.getAttribute("data-url"); if(navigator.share) navigator.share({title, url}); else alert("Share this link: " + url); });
        });
    }
    let currentCategory = "all", currentSearch = "";
    document.querySelectorAll(".filter-btn").forEach(btn => { btn.addEventListener("click", function() { document.querySelectorAll(".filter-btn").forEach(b=>b.classList.remove("active")); this.classList.add("active"); currentCategory = this.getAttribute("data-category"); renderShows(currentCategory, currentSearch); }); });
    document.getElementById("searchBtn").addEventListener("click", () => { currentSearch = document.getElementById("searchInput").value; renderShows(currentCategory, currentSearch); });
    document.getElementById("searchInput").addEventListener("keyup", e => { if(e.key === "Enter") { currentSearch = e.target.value; renderShows(currentCategory, currentSearch); } });
    renderShows("all", "");
</script>
</body>
</html>