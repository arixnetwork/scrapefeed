<?php require_once __DIR__ . '/../includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>scrapefeed — Extract Shopify &amp; WordPress product data in seconds</title>
<link rel="stylesheet" href="/assets/style.css">
<style>
  header.hero{border-bottom:1px solid var(--line);}
  .hero-inner{max-width:1120px;margin:0 auto;padding:80px 32px 60px;display:grid;grid-template-columns:1.1fr 1fr;gap:56px;align-items:center;}
  .kicker{font-size:13px;color:var(--green);margin-bottom:16px;}
  .kicker::before{content:"$ ";color:var(--muted);}
  h1.title{font-size:46px;margin-bottom:20px;}
  h1.title em{font-style:italic;color:var(--green);}
  .sub{font-size:16px;color:var(--muted);max-width:46ch;margin-bottom:28px;}
  .cta-row{display:flex;gap:12px;flex-wrap:wrap;}
  .fine{font-size:12.5px;color:var(--muted);margin-top:14px;}
  .demo{border:1px solid var(--ink);background:var(--ink);color:var(--paper);border-radius:3px;overflow:hidden;}
  .demo-bar{padding:10px 14px;border-bottom:1px solid #2a2e35;font-size:11.5px;color:#8b9099;}
  .demo-body{padding:16px;font-size:12px;}
  .demo-line{color:#c7cbd1;white-space:pre-wrap;}
  section{padding:70px 0;border-bottom:1px solid var(--line);}
  .section-head{max-width:600px;margin-bottom:40px;}
  .eyebrow{font-size:13px;color:var(--green);margin-bottom:10px;}
  .eyebrow::before{content:"// ";color:var(--muted);}
  h2.section-title{font-size:30px;margin-bottom:14px;}
  .section-sub{color:var(--muted);font-size:14.5px;max-width:56ch;}
  .rows{border-top:1px solid var(--line);}
  .row{display:grid;grid-template-columns:56px 1fr 1.4fr;gap:24px;padding:26px 0;border-bottom:1px solid var(--line);}
  .row-num{font-size:13px;color:var(--muted);}
  .row h3{font-size:18px;margin-bottom:8px;}
  .row p{color:var(--muted);font-size:14px;}
  @media (max-width:860px){
    .hero-inner{grid-template-columns:1fr;}
    h1.title{font-size:34px;}
    .row{grid-template-columns:32px 1fr;}
    .row>div:nth-child(3){grid-column:2;}
  }
</style>
</head>
<body>

<nav>
  <div class="logo"><span class="dot"></span>scrapefeed</div>
  <div class="links">
    <a href="#how">how it works</a>
    <a href="#pricing">pricing</a>
    <?php if (Auth::check()): ?>
      <a href="/dashboard.php" class="btn btn-secondary">Dashboard</a>
    <?php else: ?>
      <a href="/login.php">Log in</a>
      <a href="/register.php" class="btn btn-primary">Start free</a>
    <?php endif; ?>
  </div>
</nav>

<header class="hero grid-bg">
  <div class="hero-inner">
    <div>
      <div class="kicker">export ready-to-import product data</div>
      <h1 class="title">Every product,<br>turned into <em>clean</em> rows.</h1>
      <p class="sub">Point scrapefeed at any Shopify or WordPress/WooCommerce store. Get titles, prices, variants, images, and stock back as tidy CSV or JSON.</p>
      <div class="cta-row">
        <a href="/register.php" class="btn btn-primary">Extract your first store</a>
        <a href="#how" class="btn btn-secondary">See how it works</a>
      </div>
      <div class="fine">First <?= e(Settings::get('starter_credits', '200')) ?> products free. No card required.</div>
    </div>
    <div class="demo">
      <div class="demo-bar">extract.run</div>
      <div class="demo-body">
        <div class="demo-line">$ scrapefeed pull shop.example.com --format csv
→ detected platform: Shopify
→ crawling catalog... 486 products found
→ done in 6.2s</div>
      </div>
    </div>
  </div>
</header>

<section id="how">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">how it works</div>
      <h2 class="section-title">Three steps, no scraping know-how required.</h2>
      <p class="section-sub">Paste a URL, we detect the platform and walk the catalog, you download a file — not raw HTML to clean up yourself.</p>
    </div>
    <div class="rows">
      <div class="row"><div class="row-num">01</div><div><h3>Paste the store URL</h3></div><div><p>Any Shopify domain or WordPress/WooCommerce site. Platform is auto-detected — no API keys to hunt down.</p></div></div>
      <div class="row"><div class="row-num">02</div><div><h3>We crawl and normalize</h3></div><div><p>Titles, prices, variants, images, and stock are pulled and mapped onto one consistent schema.</p></div></div>
      <div class="row"><div class="row-num">03</div><div><h3>Download CSV or JSON</h3></div><div><p>Formatted for straight import into WooCommerce, Shopify, a spreadsheet, or your own database.</p></div></div>
    </div>
  </div>
</section>

<section id="pricing" style="border-bottom:none;">
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">pricing</div>
      <h2 class="section-title">Start free, pay only as you extract more.</h2>
      <p class="section-sub">Every plan includes CSV + JSON export and both Shopify and WooCommerce support.</p>
    </div>
    <div class="cta-row">
      <a href="/register.php" class="btn btn-primary">Create your account</a>
    </div>
  </div>
</section>

</body>
</html>
