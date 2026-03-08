<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: '.($_SESSION['role']==='admin'?'admin.php':'predict.php')); exit;
}

// Bike names grouped by brand — from your training dataset
$bike_names_by_brand = [
    'Royal Enfield' => ['Classic 350','Classic 500','Bullet 350','Bullet 500','Thunderbird 350','Thunderbird 500','Himalayan','Meteor 350','Hunter 350'],
    'Yamaha'        => ['FZ-S V3','FZ25','R15 V4','MT-15','R3','FZS-FI','Fazer 25','YZF R15','Ray ZR','Fascino','Alpha','SZ-RR'],
    'Honda'         => ['CB Shine','CB Hornet 160R','CB350','CB500F','CBR650R','Activa 6G','Unicorn','Livo','SP 125','Shine SP','CB200X','NX200'],
    'Bajaj'         => ['Pulsar 150','Pulsar 180','Pulsar 220F','Pulsar NS200','Pulsar RS200','Dominar 400','Avenger 220','CT100','Platina','Pulsar N250','Pulsar F250','Dominar 250'],
    'TVS'           => ['Apache RTR 160','Apache RTR 200','Apache RR 310','Jupiter','NTorq 125','Raider 125','Ronin','iQube Electric','Star City+','Sport','HLX 125','Radeon'],
    'KTM'           => ['Duke 200','Duke 250','Duke 390','RC 200','RC 390','Adventure 250','Adventure 390','Duke 125','RC 125','Adventure 390'],
    'Suzuki'        => ['Gixxer SF','Gixxer 250','V-Strom 650','Access 125','Burgman Street','Intruder 150','Avenis 125'],
    'Kawasaki'      => ['Ninja 300','Ninja 400','Ninja 650','Z650','Z900','Versys 650','W175','Vulcan S','Z H2','Ninja ZX-10R'],
    'Hero'          => ['Splendor Plus','Passion Pro','HF Deluxe','Glamour','Xtreme 160R','Xpulse 200','Maestro Edge','Destini 125','Super Splendor','Vida V1'],
    'Triumph'       => ['Tiger 660','Trident 660'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BikeValue — Triumph Precision Valuation</title>
<link rel="stylesheet" href="theme.css">
<style>
/* ══════════════════════════════════════
   LANDING PAGE — TRIUMPH EDITION
══════════════════════════════════════ */

/* Override the moto-bg with the ACTUAL triumph photo */
.moto-bg::before {
    background:
        url('triumphh.jpg')
        center 55% / cover no-repeat !important;
    animation: slowDrift 25s ease-in-out infinite alternate !important;
}

@keyframes slowDrift {
    from { transform: scale(1.0) translateX(0px); }
    to   { transform: scale(1.08) translateX(-20px); }
}

/* Richer cinematic overlay for THIS photo */
.moto-bg::after {
    background:
        linear-gradient(105deg,
            rgba(4,3,14,0.96)  0%,
            rgba(8,5,28,0.85)  25%,
            rgba(15,8,45,0.55) 55%,
            rgba(6,4,20,0.82)  80%,
            rgba(4,3,14,0.97)  100%),
        radial-gradient(ellipse 70% 80% at 70% 45%,
            rgba(100,60,220,0.18) 0%,
            transparent 65%) !important;
}

/* ── HERO LAYOUT ── */
.hero {
    position: relative;
    z-index: 10;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
}

.hero-body {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 4rem 4rem 2rem;
    max-width: 780px;
}

/* Live badge */
.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    background: rgba(229,57,53,0.1);
    border: 1px solid rgba(229,57,53,0.3);
    border-radius: 30px;
    padding: 0.38rem 1.1rem;
    font-size: 0.65rem;
    letter-spacing: 3px;
    color: var(--v2);
    text-transform: uppercase;
    margin-bottom: 1.8rem;
    backdrop-filter: blur(8px);
}
.live-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--v2);
    box-shadow: 0 0 10px var(--v2), 0 0 20px rgba(240,98,146,0.5);
    animation: livePulse 1.8s ease-in-out infinite;
}
@keyframes livePulse {
    0%,100% { transform: scale(1);   opacity: 1; }
    50%      { transform: scale(1.4); opacity: 0.6; }
}

/* Main title */
.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(3rem, 7.5vw, 6.2rem);
    font-weight: 900;
    line-height: 1.0;
    margin-bottom: 1.4rem;
    letter-spacing: -1px;
}
.title-line-1 { color: var(--text); display: block; }
.title-line-2 {
    display: block;
    background: linear-gradient(125deg,
        #e53935 0%, #f06292 30%,
        #0d47a1 60%, #42a5f5 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: gradientFlow 5s ease infinite;
}
.title-line-3 { color: rgba(232,232,255,0.88); display: block; font-style: italic; font-weight: 400; }

@keyframes gradientFlow {
    0%   { background-position: 0% center; }
    50%  { background-position: 100% center; }
    100% { background-position: 0% center; }
}

/* Decorative line */
.hero-accent-line {
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--v1), var(--b1), var(--cyan));
    margin-bottom: 1.8rem;
    animation: expandLine 1.2s 0.5s ease forwards;
    border-radius: 2px;
}
@keyframes expandLine { to { width: 110px; } }

.hero-sub {
    font-size: 1.05rem;
    color: rgba(196,196,232,0.8);
    line-height: 1.85;
    max-width: 490px;
    margin-bottom: 2.8rem;
    font-weight: 300;
}
.hero-sub strong {
    color: var(--v2);
    font-weight: 600;
}

/* CTA buttons */
.hero-btns {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 3.5rem;
}

/* Stats */
.hero-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(229,57,53,0.12);
}
.stat-item {}
.stat-num {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--v2), var(--v1));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
}
.stat-lbl {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: var(--muted);
    text-transform: uppercase;
    margin-top: 0.2rem;
}

/* Floating right side visual panel */
.hero-side-panel {
    position: absolute;
    right: 3rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    z-index: 5;
    animation: slideInRight 1s 0.4s ease both;
}
@keyframes slideInRight {
    from { opacity: 0; transform: translateY(-50%) translateX(40px); }
    to   { opacity: 1; transform: translateY(-50%) translateX(0); }
}
.side-card {
    background: rgba(26,13,20,0.7);
    border: 1px solid rgba(229,57,53,0.2);
    border-radius: 12px;
    padding: 1.1rem 1.4rem;
    backdrop-filter: blur(20px);
    min-width: 180px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.3s, transform 0.3s;
}
.side-card:hover {
    border-color: rgba(229,57,53,0.5);
    transform: translateX(-4px);
}
.side-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--v1), var(--v2));
}
.side-card-icon { font-size: 1.3rem; margin-bottom: 0.4rem; }
.side-card-title { font-size: 0.78rem; font-weight: 700; color: var(--text); letter-spacing: 0.5px; }
.side-card-desc  { font-size: 0.68rem; color: var(--muted); margin-top: 0.2rem; line-height: 1.4; }

/* ── FEATURES STRIP ── */
.features {
    position: relative;
    z-index: 10;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-top: 1px solid rgba(229,57,53,0.1);
    background: rgba(15,10,5,0.82);
    backdrop-filter: blur(28px);
}
.feat {
    padding: 1.8rem 1.8rem;
    border-right: 1px solid rgba(229,57,53,0.07);
    position: relative;
    transition: background 0.3s;
    overflow: hidden;
    cursor: default;
}
.feat:last-child { border-right: none; }
.feat::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--v3), var(--v1), var(--b1));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.35s ease;
}
.feat:hover { background: rgba(229,57,53,0.06); }
.feat:hover::after { transform: scaleX(1); }
.feat-icon { font-size: 1.5rem; margin-bottom: 0.6rem; display: block; }
.feat-title { font-weight: 700; font-size: 0.88rem; color: var(--text); margin-bottom: 0.25rem; }
.feat-desc  { font-size: 0.75rem; color: var(--muted); line-height: 1.5; }

/* ── PREMIUM SIDE CARDS ── */
.side-card {
    background: rgba(26,13,20,0.82);
    border: 1px solid rgba(229,57,53,0.18);
    border-radius: 14px;
    padding: 1.4rem 1.5rem;
    backdrop-filter: blur(24px);
    min-width: 210px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.35s, transform 0.35s, box-shadow 0.35s;
    cursor: default;
}
.side-card:hover {
    border-color: rgba(229,57,53,0.55);
    transform: translateX(-6px) translateY(-2px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.4), 0 0 30px rgba(229,57,53,0.12);
}
.side-card-glow {
    position: absolute;
    top: -20px; right: -20px;
    width: 80px; height: 80px;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.35s;
    pointer-events: none;
}
.side-card:hover .side-card-glow { opacity: 1; }
.side-card--ml     .side-card-glow { background: radial-gradient(circle, rgba(229,57,53,0.25), transparent 70%); }
.side-card--speed  .side-card-glow { background: radial-gradient(circle, rgba(13,71,161,0.25), transparent 70%); }
.side-card--secure .side-card-glow { background: radial-gradient(circle, rgba(240,98,146,0.25), transparent 70%); }

/* Left accent bar per card */
.side-card::before {
    content: '';
    position: absolute;
    left: 0; top: 15%; bottom: 15%;
    width: 2px;
    border-radius: 2px;
    transition: top 0.3s, bottom 0.3s;
}
.side-card--ml::before     { background: linear-gradient(180deg, var(--v1), var(--b1)); }
.side-card--speed::before  { background: linear-gradient(180deg, var(--b1), var(--b2)); }
.side-card--secure::before { background: linear-gradient(180deg, var(--v2), var(--v1)); }
.side-card:hover::before   { top: 5%; bottom: 5%; }

.side-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.8rem;
}
.side-card-icon-wrap {
    font-size: 1.4rem;
    width: 38px; height: 38px;
    background: rgba(229,57,53,0.1);
    border: 1px solid rgba(229,57,53,0.2);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
}
.side-card-tag {
    font-size: 0.55rem;
    letter-spacing: 2px;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
}
.side-card--ml     .side-card-tag { background: rgba(229,57,53,0.15); color: var(--v1); border: 1px solid rgba(229,57,53,0.3); }
.side-card--speed  .side-card-tag { background: rgba(13,71,161,0.15); color: var(--b1); border: 1px solid rgba(13,71,161,0.25); }
.side-card--secure .side-card-tag { background: rgba(240,98,146,0.15); color: var(--v2); border: 1px solid rgba(240,98,146,0.3); }

.side-card-title { font-size: 0.88rem; font-weight: 700; color: var(--text); margin-bottom: 0.3rem; letter-spacing: 0.3px; }
.side-card-desc  { font-size: 0.7rem; color: var(--muted); line-height: 1.5; margin-bottom: 0.9rem; }

.side-card-bar {
    height: 3px;
    background: rgba(255,255,255,0.06);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.35rem;
}
.side-card-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 1.5s ease;
}
.side-card--ml     .side-card-bar-fill { background: linear-gradient(90deg, var(--v1), var(--v3)); }
.side-card--speed  .side-card-bar-fill { background: linear-gradient(90deg, var(--b1), var(--b2)); }
.side-card--secure .side-card-bar-fill { background: linear-gradient(90deg, var(--v2), var(--v1)); }
.side-card-bar-label { font-size: 0.6rem; color: var(--muted); letter-spacing: 1px; }

/* ── SCROLL INDICATOR ── */
.scroll-hint {
    position: absolute;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    z-index: 10;
    animation: fadeIn 2s 1.5s ease both;
    opacity: 0;
}
.scroll-hint span { font-size: 0.6rem; letter-spacing: 3px; color: var(--muted); text-transform: uppercase; }
.scroll-arrow {
    width: 20px; height: 30px;
    border: 1px solid rgba(229,57,53,0.35);
    border-radius: 10px;
    display: flex; justify-content: center;
    padding-top: 5px;
}
.scroll-arrow::before {
    content: '';
    width: 4px; height: 8px;
    background: var(--v2);
    border-radius: 2px;
    animation: scrollBounce 1.5s ease-in-out infinite;
}
@keyframes scrollBounce {
    0%,100% { transform: translateY(0); opacity: 1; }
    50%      { transform: translateY(6px); opacity: 0.4; }
}
@keyframes fadeIn { to { opacity: 1; } }

@media (max-width: 1100px) { .hero-side-panel { display: none; } }
@media (max-width: 768px) {
    .hero-body { padding: 3rem 1.5rem 2rem; }
    .features  { grid-template-columns: 1fr 1fr; }
    .feat { border-right: none; border-bottom: 1px solid rgba(124,92,252,0.07); }
}
@media (max-width: 480px) { .features { grid-template-columns: 1fr; } }

/* ══════════════════════════════════════
   PREMIUM FEATURES SECTION (BOTTOM)
══════════════════════════════════════ */

.premium-features {
    position: relative;
    z-index: 10;
    padding: 5rem 4rem 4rem;
    background: linear-gradient(180deg,
        rgba(229,57,53,0.03) 0%,
        rgba(240,98,146,0.02) 50%,
        rgba(13,71,161,0.03) 100%);
}

.features-container {
    max-width: 1200px;
    margin: 0 auto;
}

.features-header {
    text-align: center;
    margin-bottom: 3.5rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2.2rem;
}

.feature-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 2rem;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    position: relative;
    overflow: hidden;
    transition: border-color 0.35s, transform 0.35s, box-shadow 0.35s;
    cursor: default;
}

.feature-card:hover {
    border-color: rgba(229,57,53,0.55);
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 30px rgba(229,57,53,0.15);
}

.feature-card-glow {
    position: absolute;
    top: -20px; right: -20px;
    width: 80px; height: 80px;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.35s;
    pointer-events: none;
}

.feature-card:hover .feature-card-glow { opacity: 1; }

.feature-card--ml     .feature-card-glow { background: radial-gradient(circle, rgba(229,57,53,0.25), transparent 70%); }
.feature-card--speed  .feature-card-glow { background: radial-gradient(circle, rgba(13,71,161,0.25), transparent 70%); }
.feature-card--secure .feature-card-glow { background: radial-gradient(circle, rgba(240,98,146,0.25), transparent 70%); }

.feature-card::before {
    content: '';
    position: absolute;
    left: 0; top: 15%; bottom: 15%;
    width: 2px;
    border-radius: 2px;
    transition: top 0.3s, bottom 0.3s;
}

.feature-card--ml::before     { background: linear-gradient(180deg, var(--v1), var(--v3)); }
.feature-card--speed::before  { background: linear-gradient(180deg, var(--b1), var(--b2)); }
.feature-card--secure::before { background: linear-gradient(180deg, var(--v2), var(--v1)); }

.feature-card:hover::before   { top: 5%; bottom: 5%; }

.feature-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.8rem;
}

.feature-card-icon {
    font-size: 1.8rem;
}

.feature-card-tag {
    font-size: 0.55rem;
    letter-spacing: 2px;
    font-weight: 700;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

.feature-card--ml     .feature-card-tag { background: rgba(229,57,53,0.15); color: var(--v1); border: 1px solid rgba(229,57,53,0.3); }
.feature-card--speed  .feature-card-tag { background: rgba(13,71,161,0.15); color: var(--b1); border: 1px solid rgba(13,71,161,0.3); }
.feature-card--secure .feature-card-tag { background: rgba(240,98,146,0.15); color: var(--v2); border: 1px solid rgba(240,98,146,0.3); }

.feature-card-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 0.4rem; letter-spacing: 0.3px; }
.feature-card-desc  { font-size: 0.75rem; color: var(--muted); line-height: 1.5; margin-bottom: 1rem; }

.feature-card-bar {
    height: 3px;
    background: rgba(255,255,255,0.06);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.35rem;
}

.feature-card-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 1.5s ease;
}

.feature-card--ml     .feature-card-bar-fill { background: linear-gradient(90deg, var(--v1), var(--v3)); }
.feature-card--speed  .feature-card-bar-fill { background: linear-gradient(90deg, var(--b1), var(--b2)); }
.feature-card--secure .feature-card-bar-fill { background: linear-gradient(90deg, var(--v2), var(--v1)); }

.feature-card-bar-label { font-size: 0.6rem; color: var(--muted); letter-spacing: 1px; }

@media (max-width: 1100px) { .hero-side-panel { display: none; } }
@media (max-width: 768px) {
    .hero-body { padding: 3rem 1.5rem 2rem; }
    .features  { grid-template-columns: 1fr 1fr; }
    .feat { border-right: none; border-bottom: 1px solid rgba(124,92,252,0.07); }
    .premium-features { padding: 3rem 1.5rem 2rem; }
    .features-grid { grid-template-columns: 1fr; gap: 1.5rem; }
}
@media (max-width: 480px) { 
    .features { grid-template-columns: 1fr; }
    .features-header h2 { font-size: 1.5rem; }
}
</style>
</head>
<body>
<div class="moto-bg"></div>
<div class="light-bleed"></div>
<div class="grain"></div>
<div class="grid-overlay"></div>

<!-- ══ NAVBAR ══ -->
<nav class="navbar">
    <a class="nav-brand" href="index.php">⚡ BIKE<span>VALUE</span></a>
    <div class="nav-actions">
        <button class="btn btn-info" onclick="openModal('helpModal')" title="Help & Instructions">ⓘ</button>
        <button class="btn btn-ghost"   onclick="openModal('loginModal')">Sign In</button>
        <button class="btn btn-primary" onclick="openModal('signupModal')">Get Started →</button>
        <button class="btn btn-admin"   onclick="openModal('adminModal')">⚙ Admin</button>
    </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-body">
        <div>
            <!-- Live badge -->
            <div class="live-badge anim-1">
                <span class="live-dot"></span>
                Triumph Precision Valuation Engine
            </div>

            <!-- Title -->
            <h1 class="hero-title anim-2">
                <span class="title-line-1">Know The</span>
                <span class="title-line-2">True Value</span>
                <span class="title-line-3">Of Your Ride</span>
            </h1>

            <div class="hero-accent-line"></div>

            <p class="hero-sub anim-3">
                Powered by a <strong>Random Forest ML model</strong> trained on real
                Indian market data. Get instant predictions using accident history,
                city data &amp; engine specs.
            </p>

            <div class="hero-btns anim-3">
                <button class="btn btn-primary btn-lg" onclick="openModal('signupModal')">
                    🏍 Start Free Valuation
                </button>
                <button class="btn btn-outline btn-lg" onclick="openModal('loginModal')">
                    Already a Member
                </button>
            </div>

            <div class="hero-stats anim-4">
                <div class="stat-item">
                    <div class="stat-num">10+</div>
                    <div class="stat-lbl">Brands</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num">120+</div>
                    <div class="stat-lbl">Bike Models</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num">RF</div>
                    <div class="stat-lbl">ML Model</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num">&lt;2s</div>
                    <div class="stat-lbl">Prediction</div>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ══ SIGNUP MODAL ══ -->
<div id="signupModal" class="modal-overlay" onclick="overlayClose(event,'signupModal')">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Create Account</h2>
            <button class="modal-close" onclick="closeModal('signupModal')">✕</button>
        </div>
        <?php if(!empty($_SESSION['auth_error'])&&($_SESSION['open_modal']??'')==='signupModal'): ?>
            <div class="alert alert-error"><?=htmlspecialchars($_SESSION['auth_error'])?></div>
            <?php unset($_SESSION['auth_error']); ?>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="hidden" name="action" value="signup">
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Username</label>
                <input type="text" name="user_id" class="form-input" placeholder="e.g. john_rider" required>
            </div>
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="your@email.com" required>
            </div>
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Min. 6 characters" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Create Account →</button>
            </div>
            <div class="modal-switch">Already registered? <a onclick="switchModal('signupModal','loginModal')">Sign in here</a></div>
        </form>
    </div>
</div>

<!-- ══ LOGIN MODAL ══ -->
<div id="loginModal" class="modal-overlay" onclick="overlayClose(event,'loginModal')">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Welcome Back</h2>
            <button class="modal-close" onclick="closeModal('loginModal')">✕</button>
        </div>
        <?php if(!empty($_SESSION['auth_error'])&&($_SESSION['open_modal']??'')==='loginModal'): ?>
            <div class="alert alert-error"><?=htmlspecialchars($_SESSION['auth_error'])?></div>
            <?php unset($_SESSION['auth_error']); ?>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="hidden" name="action" value="user_login">
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="your@email.com" required>
            </div>
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Your password" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Sign In →</button>
            </div>
            <div class="modal-switch">New here? <a onclick="switchModal('loginModal','signupModal')">Create an account</a></div>
        </form>
    </div>
</div>

<!-- ══ ADMIN MODAL ══ -->
<div id="adminModal" class="modal-overlay" onclick="overlayClose(event,'adminModal')">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Admin Access</h2>
            <button class="modal-close" onclick="closeModal('adminModal')">✕</button>
        </div>
        <?php if(!empty($_SESSION['auth_error'])&&($_SESSION['open_modal']??'')==='adminModal'): ?>
            <div class="alert alert-error"><?=htmlspecialchars($_SESSION['auth_error'])?></div>
            <?php unset($_SESSION['auth_error']); ?>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="hidden" name="action" value="admin_login">
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-input" placeholder="admin@bikevalue.com" required>
            </div>
            <div class="form-group" style="margin-bottom:1.1rem">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="Admin password" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-admin" style="flex:1;justify-content:center;">⚙ Enter Dashboard</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ HELP & INSTRUCTIONS MODAL ══ -->
<div id="helpModal" class="modal-overlay" onclick="overlayClose(event,'helpModal')">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Help & Instructions</h2>
            <button class="modal-close" onclick="closeModal('helpModal')">✕</button>
        </div>
        <div style="color:var(--text2);font-size:0.9rem;line-height:1.8;max-height:70vh;overflow-y:auto">
            <h3 style="color:var(--v1);font-size:1.1rem;margin-bottom:0.8rem;font-weight:700">🏍 Welcome to BikeValue</h3>
            <p style="margin-bottom:1rem">
                BikeValue is an AI-powered motorcycle valuation engine that predicts your bike's real market value based on machine learning analysis of Indian market data.
            </p>
            
            <h3 style="color:var(--v1);font-size:1rem;margin-top:1.2rem;margin-bottom:0.8rem;font-weight:700">📋 How It Works</h3>
            <ol style="padding-left:1.5rem;margin-bottom:1rem">
                <li style="margin-bottom:0.5rem"><strong>Create an Account:</strong> Sign up with your email and password</li>
                <li style="margin-bottom:0.5rem"><strong>Enter Bike Details:</strong> Select your bike's brand, model, and engine capacity</li>
                <li style="margin-bottom:0.5rem"><strong>Provide Additional Info:</strong> Add bike age, ownership count, kilometers driven, city, and accident history</li>
                <li style="margin-bottom:0.5rem"><strong>Get Instant Valuation:</strong> Our ML model analyzes all data and returns an accurate price prediction</li>
            </ol>

            <h3 style="color:var(--v1);font-size:1rem;margin-top:1.2rem;margin-bottom:0.8rem;font-weight:700">✨ Key Features</h3>
            <ul style="padding-left:1.5rem;margin-bottom:1rem">
                <li style="margin-bottom:0.5rem"><strong>ML Powered:</strong> Random Forest model trained on real Indian market data (92% accuracy)</li>
                <li style="margin-bottom:0.5rem"><strong>Instant Results:</strong> Get valuations in under 2 seconds</li>
                <li style="margin-bottom:0.5rem"><strong>Secure:</strong> Your data is encrypted with bcrypt and stored securely</li>
                <li style="margin-bottom:0.5rem"><strong>Premium Experience:</strong> Beautiful, intuitive interface designed for bike enthusiasts</li>
            </ul>

            <h3 style="color:var(--v1);font-size:1rem;margin-top:1.2rem;margin-bottom:0.8rem;font-weight:700">💡 Tips for Best Results</h3>
            <ul style="padding-left:1.5rem;margin-bottom:1rem">
                <li style="margin-bottom:0.5rem">Be accurate with mileage and bike age for better predictions</li>
                <li style="margin-bottom:0.5rem">Report any accident history for realistic adjusted valuations</li>
                <li style="margin-bottom:0.5rem">Select the correct city where the bike is registered</li>
                <li style="margin-bottom:0.5rem">You can refine your inputs and re-run the valuation anytime</li>
            </ul>

            <h3 style="color:var(--v1);font-size:1rem;margin-top:1.2rem;margin-bottom:0.8rem;font-weight:700">❓ Common Questions</h3>
            <p style="margin-bottom:0.5rem"><strong>Is my data safe?</strong> Yes! All data is encrypted and only used for valuations.</p>
            <p style="margin-bottom:0.5rem"><strong>Can I edit my predictions?</strong> Yes! Click "Refine Inputs" to adjust and re-run.</p>
            <p style="margin-bottom:0.5rem"><strong>How accurate are the predictions?</strong> Our ML model achieves 92% accuracy on test data.</p>
        </div>
    </div>
</div>

<script>
function openModal(id) {
  // Clear all form fields in this modal
  const modal = document.getElementById(id);
  const inputs = modal.querySelectorAll('input, textarea, select');
  inputs.forEach(input => {
    if (input.type === 'hidden') return; // Don't clear hidden fields (like action)
    if (input.type === 'checkbox' || input.type === 'radio') {
      input.checked = false;
    } else {
      input.value = '';
    }
  });
  modal.classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

function overlayClose(e,id) {
  if(e.target.id===id) closeModal(id);
}

function switchModal(a,b){
  closeModal(a);
  openModal(b);
}

document.addEventListener('keydown', e => {
    if(e.key==='Escape')
        document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'));
});
</script>
<?php if(!empty($_SESSION['open_modal'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('<?=$_SESSION['open_modal']?>'));</script>
<?php unset($_SESSION['open_modal']); endif; ?>
</body>
</html>
