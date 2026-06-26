<?php
// ============================================================
//  Academon · dashboard.php  (example protected page)
//  Replace this file with your real dashboard.
//  The auth_guard at the top handles the redirect-if-not-logged-in.
// ============================================================
require_once __DIR__ . '/auth_guard.php';
// $current_user['username'] and $current_user['id'] are now available
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academon · Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: url('sunset.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Press Start 2P', monospace;
            color: #1e1a2b;
        }

        /* ── NAV ── */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            background: rgba(30, 26, 43, 0.88);
            border-bottom: 2px solid rgba(255, 215, 100, 0.4);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(6px);
        }

        .navbar-logo {
            height: 34px;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));
            image-rendering: auto;
            cursor: pointer;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .trainer-tag {
            font-size: 8px;
            color: #f5e9c0;
            letter-spacing: 0.5px;
        }

        .trainer-tag span { color: #ffd764; }

        .login-btn {
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            color: #f5e9c0;
            background: rgba(255, 215, 100, 0.15);
            border: 2px solid rgba(255, 215, 100, 0.5);
            border-radius: 20px;
            padding: 7px 14px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            background: rgba(255, 215, 100, 0.3);
            color: #ffd764;
            transform: scale(1.05);
        }

        .logout-btn {
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            color: #f5e9c0;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 215, 100, 0.4);
            border-radius: 20px;
            padding: 7px 14px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
            letter-spacing: 0.5px;
        }

        .logout-btn:hover {
            background: rgba(255, 215, 100, 0.15);
            color: #ffd764;
        }

        /* ── BODY ── */
        .main {
            max-width: 860px;
            margin: 0 auto;
            padding: 28px 18px 60px;
        }

        /* ── GREETING ── */
        .greeting-card {
            background: rgba(255, 241, 203, 0.93);
            border: 3px solid rgba(255, 215, 100, 0.85);
            border-radius: 22px;
            padding: 22px 26px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.28);
            flex-wrap: wrap;
        }

        .greeting-icon {
            font-size: 40px;
            flex-shrink: 0;
            animation: bob 2.6s ease-in-out infinite;
        }

        @keyframes bob {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-6px); }
        }

        .greeting-text h1 {
            font-size: 11px;
            line-height: 1.8;
            color: #1e1a2b;
            margin-bottom: 8px;
        }

        .greeting-text p {
            font-size: 8px;
            color: #4a4060;
            line-height: 2;
        }

        /* ── SECTION LABEL ── */
        .sec-label {
            font-size: 8px;
            color: rgba(255, 241, 203, 0.78);
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        /* ── CARD GRID ── */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        /* ── GAME CARD ── */
        .game-card {
            background: rgba(255, 241, 203, 0.93);
            border: 2.5px solid rgba(255, 215, 100, 0.75);
            border-radius: 18px;
            padding: 18px 16px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.24), 0 3px 0 rgba(160,120,0,0.3);
            display: flex;
            flex-direction: column;
            gap: 9px;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .game-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.28), 0 3px 0 rgba(160,120,0,0.3);
        }

        .card-icon { font-size: 24px; }

        .game-card h3 {
            font-size: 9px;
            color: #1e1a2b;
            line-height: 1.7;
        }

        .game-card p {
            font-size: 7.5px;
            color: #4a4060;
            line-height: 2;
        }

        /* ── PILLS ── */
        .pill {
            display: inline-block;
            font-family: 'Press Start 2P', monospace;
            font-size: 7.5px;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .pill-amber  { background: #f6c443; color: #1e1a2b; }
        .pill-red    { background: #e07070; color: #fff; }
        .pill-purple { background: #a78bfa; color: #fff; }

        /* ── XP BAR ── */
        .xp-label {
            font-size: 7.5px;
            color: #4a4060;
            margin-bottom: 5px;
        }

        .xp-track {
            background: #d6ceee;
            border-radius: 5px;
            height: 9px;
            overflow: hidden;
        }

        .xp-fill {
            height: 9px;
            background: linear-gradient(90deg, #8f8df4, #c4b5fd);
            border-radius: 5px;
            width: 0%;
            transition: width 1.3s cubic-bezier(.4,0,.2,1);
        }

        /* ── CARD BUTTON ── */
        .card-btn {
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            padding: 9px 14px;
            border-radius: 28px;
            border: none;
            cursor: pointer;
            background: #2c2025;
            color: #fff;
            box-shadow: 0 4px 0 #0f0b0e;
            transition: 0.12s;
            text-decoration: none;
            display: inline-block;
            margin-top: 2px;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .card-btn:hover  { background: #1f151a; transform: translateY(-1px); box-shadow: 0 5px 0 #0f0b0e; }
        .card-btn:active { transform: translateY(3px); box-shadow: 0 1px 0 #0f0b0e; }

        /* ── SPECIAL PROFESSOR BUTTON ── */
        .card-btn-professor {
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            padding: 9px 14px;
            border-radius: 28px;
            border: none;
            cursor: pointer;
            background: #8f8df4;
            color: #fff;
            box-shadow: 0 4px 0 #6b69b8;
            transition: 0.12s;
            text-decoration: none;
            display: inline-block;
            margin-top: 2px;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .card-btn-professor:hover  { background: #7a78d6; transform: translateY(-1px); box-shadow: 0 5px 0 #6b69b8; }
        .card-btn-professor:active { transform: translateY(3px); box-shadow: 0 1px 0 #6b69b8; }

        /* ── WIDE CARD ── */
        .wide-card {
            background: rgba(255, 241, 203, 0.93);
            border: 2.5px solid rgba(255, 215, 100, 0.75);
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.24), 0 3px 0 rgba(160,120,0,0.3);
            margin-bottom: 24px;
        }

        .wide-card h3 {
            font-size: 9px;
            color: #1e1a2b;
            margin-bottom: 14px;
        }

        /* ── SUBJECT BARS ── */
        .subj-list { list-style: none; }

        .subj-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px dashed rgba(30,26,43,0.13);
            font-size: 8px;
            color: #3a3050;
        }

        .subj-row:last-child { border-bottom: none; }

        .subj-name { width: 70px; flex-shrink: 0; }

        .subj-bar-wrap {
            flex: 1;
            background: #d6ceee;
            border-radius: 4px;
            height: 7px;
            overflow: hidden;
        }

        .subj-bar {
            height: 7px;
            border-radius: 4px;
            background: #8f8df4;
            width: 0%;
            transition: width 1.4s cubic-bezier(.4,0,.2,1);
        }

        .subj-pct {
            font-size: 7.5px;
            color: #4a4060;
            width: 32px;
            text-align: right;
            flex-shrink: 0;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .navbar { padding: 10px 14px; }
            .trainer-tag { display: none; }
            .main { padding: 18px 12px 50px; }
            .greeting-text h1 { font-size: 9px; }
            .card-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 420px) {
            .card-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="login.php">
        <img class="navbar-logo" src="academon.png" alt="Academon" />
    </a>
    <div class="navbar-right">
        <div class="trainer-tag">TRAINER: <span><?php echo htmlspecialchars($current_user['username']); ?></span></div>
        <a class="login-btn" href="login.php">🔑 LOGIN</a>
        <a class="logout-btn" href="logout.php">LOGOUT</a>
    </div>
</nav>

<main class="main">

    <!-- GREETING -->
    <div class="greeting-card">
        <div class="greeting-icon">⚔️</div>
        <div class="greeting-text">
            <h1>WELCOME BACK,<br><?php echo htmlspecialchars($current_user['username']); ?>!</h1>
            <p>You are logged in.<br>Replace this page with your real dashboard.</p>
        </div>
    </div>

    <!-- STATS -->
    <p class="sec-label">✦ TRAINER STATS</p>
    <div class="card-grid">

        <div class="game-card">
            <div class="card-icon">🏅</div>
            <h3>BADGES EARNED</h3>
            <span class="pill pill-amber" id="badge-count">2 / 8</span>
            <p>Win battles to collect subject badges.</p>
        </div>

        <div class="game-card">
            <div class="card-icon">🔥</div>
            <h3>WIN STREAK</h3>
            <span class="pill pill-red" id="streak-count">3 DAYS</span>
            <p>Keep battling daily to extend your streak!</p>
        </div>

        <div class="game-card">
            <div class="card-icon">⚡</div>
            <h3>TRAINER LEVEL</h3>
            <span class="pill pill-purple" id="level-label">LV. 3</span>
            <div class="xp-label" id="xp-label">XP: 35 / 100</div>
            <div class="xp-track"><div class="xp-fill" id="xp-fill"></div></div>
        </div>

    </div>

    <!-- QUICK ACTIONS -->
    <p class="sec-label">✦ QUICK ACTIONS</p>
    <div class="card-grid">

        <div class="game-card">
            <div class="card-icon">⚔️</div>
            <h3>BATTLE NOW</h3>
            <p>Jump into a battle and test your knowledge.</p>
            <a class="card-btn" href="academon_battle_prototype.html">START BATTLE</a>
        </div>

        <div class="game-card">
            <div class="card-icon">📝</div>
            <h3>MY NOTES</h3>
            <p>Review saved notes before heading into battle.</p>
            <a class="card-btn" href="academon_battle_prototype.html#notes">VIEW NOTES</a>
        </div>

        <div class="game-card">
            <div class="card-icon">🧠</div>
            <h3>AI REVIEW</h3>
            <p>Ask the professor AI about your subjects.</p>
            <!-- UPDATED: Links to reviewbot.php -->
            <a class="card-btn-professor" href="reviewbot.php">🤖 ASK PROFESSOR</a>
        </div>

    </div>

    <!-- SUBJECT PROGRESS -->
    <div class="wide-card">
        <h3>📊 SUBJECT PROGRESS</h3>
        <ul class="subj-list">
            <li class="subj-row">
                <span class="subj-name">MATH</span>
                <div class="subj-bar-wrap"><div class="subj-bar" data-pct="72"></div></div>
                <span class="subj-pct">72%</span>
            </li>
            <li class="subj-row">
                <span class="subj-name">SCIENCE</span>
                <div class="subj-bar-wrap"><div class="subj-bar" data-pct="55"></div></div>
                <span class="subj-pct">55%</span>
            </li>
            <li class="subj-row">
                <span class="subj-name">HISTORY</span>
                <div class="subj-bar-wrap"><div class="subj-bar" data-pct="40"></div></div>
                <span class="subj-pct">40%</span>
            </li>
            <li class="subj-row">
                <span class="subj-name">ENGLISH</span>
                <div class="subj-bar-wrap"><div class="subj-bar" data-pct="88"></div></div>
                <span class="subj-pct">88%</span>
            </li>
            <li class="subj-row">
                <span class="subj-name">FILIPINO</span>
                <div class="subj-bar-wrap"><div class="subj-bar" data-pct="30"></div></div>
                <span class="subj-pct">30%</span>
            </li>
        </ul>
    </div>

</main>

<script>
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.getElementById('xp-fill').style.width = '35%';
            document.querySelectorAll('.subj-bar').forEach(bar => {
                bar.style.width = bar.dataset.pct + '%';
            });
        }, 200);
    });
</script>

</body>
</html>