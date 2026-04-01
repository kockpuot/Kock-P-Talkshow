<?php
// index.php - Home page with dynamic content from database
require_once 'database/kock_p_talkshow.php';

// Fetch latest talks (order by display_order, limit 6)
$stmt = $pdo->query("SELECT * FROM home_shows WHERE is_featured = 0 ORDER BY display_order ASC, created_at DESC LIMIT 6");
$latestShows = $stmt->fetchAll();

// Fetch featured talk (only one)
$stmt = $pdo->query("SELECT * FROM home_shows WHERE is_featured = 1 LIMIT 1");
$featured = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kock P Talkshow – Interviews, Podcasts & History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (Keep all your existing styles, just add logo style) */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height:1.6; background:#f9f9f9; color:#222; }
        .container { max-width:1200px; margin:auto; padding:0 20px; }
        header { background:#1a1a2e; color:white; padding:1rem 0; position:sticky; top:0; z-index:100; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        nav { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .logo { display:flex; align-items:center; gap:10px; font-size:1.8rem; font-weight:bold; }
        .logo img { height:50px; width:auto; }
        .logo span { color:#e94560; }
        .nav-links { display:flex; gap:1.5rem; list-style:none; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; transition:0.3s; }
        .nav-links a:hover { color:#e94560; }
        .hero { background:linear-gradient(135deg, #16213e, #0f3460); color:white; padding:4rem 0; text-align:center; }
        .hero h1 { font-size:2.8rem; margin-bottom:1rem; }
        .hero p { font-size:1.2rem; max-width:700px; margin:0 auto 2rem; }
        .btn { display:inline-block; background:#e94560; color:white; padding:12px 28px; border-radius:40px; text-decoration:none; font-weight:bold; margin:0 8px; transition:0.3s; border:none; cursor:pointer; }
        .btn-outline { background:transparent; border:2px solid #e94560; }
        .btn:hover { background:#ff6b6b; transform:scale(1.02); }
        .section-title { font-size:2rem; margin:2rem 0 1.5rem; text-align:center; position:relative; }
        .section-title:after { content:''; display:block; width:80px; height:4px; background:#e94560; margin:10px auto 0; }
        .cards-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:2rem; margin:2rem 0; }
        .card { background:white; border-radius:12px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.1); transition:0.3s; }
        .card:hover { transform:translateY(-5px); }
        .card img { width:100%; height:200px; object-fit:cover; background:#1a1a2e; }
        .card-content { padding:1.5rem; }
        .card h3 { margin-bottom:0.5rem; }
        .card p { color:#555; margin-bottom:1rem; }
        .card .tag { background:#eee; display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.8rem; margin-bottom:10px; }
        .featured { background:white; border-radius:20px; display:flex; flex-wrap:wrap; margin:2rem 0; box-shadow:0 10px 20px rgba(0,0,0,0.1); }
        .featured-media { flex:1; min-width:280px; background:#000; border-radius:20px 0 0 20px; overflow:hidden; }
        .featured-media iframe { width:100%; height:100%; min-height:280px; }
        .featured-content { flex:1; padding:2rem; }
        .cta-banner { background:#1a1a2e; color:white; text-align:center; padding:3rem; border-radius:24px; margin:3rem 0; }
        .cta-banner h2 { font-size:2rem; margin-bottom:1rem; }
        .cta-buttons { margin-top:1.5rem; }
        footer { background:#0a0a1a; color:#ccc; text-align:center; padding:2rem; margin-top:3rem; }
        .social-links { margin:1rem 0; }
        .social-links a { color:#ccc; margin:0 10px; font-size:1.5rem; transition:0.3s; display:inline-block; }
        .social-links a:hover { color:#e94560; transform:scale(1.1); }
        .contact-info p { margin:5px 0; }
        @media (max-width:768px) {
            .nav-links { margin-top:1rem; justify-content:center; width:100%; }
            .hero h1 { font-size:2rem; }
            .btn { margin-bottom:10px; }
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo">
                <img src="images/logo.png" alt="Kock P Talkshow">
                Kock P <span>Talkshow</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="shows.php">Shows</a></li>
                <li><a href="request_talk.php">Request Talk</a></li>
                <li><a href="share_history.php">Share Story</a></li>
                <li><a href="advertise.php">Advertise</a></li>
                <li><a href="suggest_guest.php">Suggest Guest</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Real Conversations. Bold Stories.</h1>
        <p>Interviews, podcasts, hidden history, and community voices — all in one place.</p>
        <a href="shows.php" class="btn">Watch Latest</a>
        <a href="request_talk.php" class="btn btn-outline">Be a Guest</a>
    </div>
</section>

<div class="container">
    <!-- Latest Talks (dynamic) -->
    <h2 class="section-title">Latest Talks</h2>
    <div class="cards-grid">
        <?php if (count($latestShows) > 0): ?>
            <?php foreach ($latestShows as $show): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($show['image_url'] ?: 'https://placehold.co/600x400/1a1a2e/white?text='.urlencode($show['category'])); ?>" alt="<?php echo htmlspecialchars($show['title']); ?>">
                    <div class="card-content">
                        <span class="tag">
                            <?php 
                                $icons = ['interview'=>'🎙️', 'podcast'=>'🎧', 'history'=>'📜', 'special'=>'💬'];
                                echo $icons[$show['category']] . ' ' . ucfirst($show['category']);
                            ?>
                        </span>
                        <h3><?php echo htmlspecialchars($show['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($show['description'], 0, 100)) . '...'; ?></p>
                        <a href="<?php echo htmlspecialchars($show['embed_url']); ?>" target="_blank" class="btn" style="padding: 8px 20px;">Watch →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No shows available. Please check back later.</p>
        <?php endif; ?>
    </div>

    <!-- Featured Talk of the Week (dynamic) -->
    <h2 class="section-title">🎬 Featured Talk of the Week</h2>
    <?php if ($featured): ?>
        <div class="featured">
            <div class="featured-media">
                <iframe src="<?php echo htmlspecialchars($featured['embed_url']); ?>" title="<?php echo htmlspecialchars($featured['title']); ?>" frameborder="0" allowfullscreen></iframe>
            </div>
            <div class="featured-content">
                <span class="tag" style="background:#e94560; color:white;">🔥 Special</span>
                <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
                <p><?php echo htmlspecialchars($featured['description']); ?></p>
                <a href="<?php echo htmlspecialchars($featured['embed_url']); ?>" target="_blank" class="btn">Watch Full Episode →</a>
            </div>
        </div>
    <?php else: ?>
        <p>No featured talk this week. Check back soon!</p>
    <?php endif; ?>

    <!-- Call-to-Action Banner -->
    <div class="cta-banner">
        <h2>Want to be part of the show?</h2>
        <p>Request an interview, share your untold history, or promote your business.</p>
        <div class="cta-buttons">
            <a href="request_talk.php" class="btn">Request a Talk</a>
            <a href="share_history.php" class="btn btn-outline" style="border-color: white;">Share Your Story</a>
            <a href="advertise.php" class="btn btn-outline" style="border-color: white;">Advertise With Us</a>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 Kock P Talkshow – Amplifying voices that matter.</p>
        <div class="social-links">
            <a href="https://facebook.com/kockp01" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://youtube.com/@kockp01" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="https://instagram.com/kockp01" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://tiktok.com/@kockp01" target="_blank"><i class="fab fa-tiktok"></i></a>
            <a href="https://wa.me/251993557112" target="_blank"><i class="fab fa-whatsapp"></i></a>
            <a href="https://t.me/kockpuot" target="_blank"><i class="fab fa-telegram"></i></a>
            <a href="mailto:kockpuot@gmail.com"><i class="fas fa-envelope"></i></a>
        </div>
        <div class="contact-info">
            <p>Email: kockpuot@gmail.com | WhatsApp: +251993557112 | Telegram: @kockpuot</p>
        </div>
    </div>
</footer>

</body>
</html>