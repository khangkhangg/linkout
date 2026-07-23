<?php
$pageTitle = ($title ?? 'LinkOut') === 'LinkOut'
    ? 'LinkOut — Anonymous exit stories & company reviews'
    : ($title ?? 'LinkOut') . ' · LinkOut';
$metaDesc = $meta_description ?? 'Real, anonymous exit stories from people who left their jobs. Read and rate companies on leadership, culture, pay, work-life balance, growth and how they handle departures.';
$canonical = 'https://linkout.didudi.com' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$ogType = $og_type ?? 'website';
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$noindex = ($noindex ?? false)
    || (bool) preg_match('#^/(admin|post|login|signup|reset|confirm|lang|api|search|story/\d+/edit)#', $path);
?><!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php if ($noindex): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
  <meta property="og:site_name" content="LinkOut">
  <meta property="og:type" content="<?= e($ogType) ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDesc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDesc) ?>">
  <?php if (!empty($json_ld)): ?>
  <script type="application/ld+json"><?= json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <?php endif; ?>
  <?php // Google Analytics / AdSense — only on public (indexable) pages. IDs are
  // format-validated on save; still rawurlencode/json_encode here for defense.
  if (!$noindex):
    $gaId = setting_get(db(), 'ga_measurement_id', '');
    $adsClient = setting_get(db(), 'adsense_client', '');
  ?>
    <?php if ($gaId !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= rawurlencode($gaId) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($gaId) ?>);</script>
    <?php endif; ?>
    <?php if ($adsClient !== ''): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= rawurlencode($adsClient) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <?php $announcement = setting_get(db(), 'announcement', ''); ?>
  <?php if ($announcement !== ''): ?>
  <div class="site-announce"><?= e($announcement) ?></div>
  <?php endif; ?>
  <nav class="nav">
    <a class="nav-logo" href="/">
      <svg class="logo-mark" width="30" height="30" viewBox="0 0 30 30" fill="none"
           xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect x="2.5" y="2.5" width="17" height="25" rx="5.5" fill="#fdedea"/>
        <path d="M8 2.5h6.5a5.5 5.5 0 0 1 5.5 5.5v14a5.5 5.5 0 0 1-5.5 5.5H8"
              stroke="#f73b20" stroke-width="2.6" stroke-linecap="round" fill="none"/>
        <path d="M12.5 15h14.5m0 0-4.6-4.6M27 15l-4.6 4.6"
              stroke="#f73b20" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="logo-text">Link<span class="logo-text-accent">Out</span></span>
    </a>
    <form class="nav-search" action="/search" method="get">
      <input type="search" name="q" placeholder="<?= e(t('nav_search_placeholder')) ?>" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="nav-right">
      <a class="lang-toggle" href="/lang/<?= current_lang() === 'vi' ? 'en' : 'vi' ?>">
        <?= current_lang() === 'vi' ? 'EN' : 'VI' ?></a>
      <?php if ($u = current_user()): ?>
        <a class="btn-primary" href="/post"><?= e(t('nav_share')) ?></a>
        <details class="user-menu">
          <summary class="user-menu-trigger" aria-label="<?= e($u['handle']) ?>">
            <span class="avatar" style="background: <?= e(avatar_color($u['handle'])) ?>"><?= e(mb_strtoupper(mb_substr($u['handle'], 0, 1))) ?></span>
            <svg class="caret" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </summary>
          <div class="user-menu-panel">
            <div class="user-menu-head">
              <span class="avatar avatar-lg" style="background: <?= e(avatar_color($u['handle'])) ?>"><?= e(mb_strtoupper(mb_substr($u['handle'], 0, 1))) ?></span>
              <div>
                <div class="user-menu-handle"><?= e($u['handle']) ?></div>
                <div class="muted user-menu-role"><?= e($u['role'] === 'admin' ? t('nav_admin') : t('user_menu_member')) ?></div>
              </div>
            </div>
            <a class="user-menu-item" href="/post"><?= e(t('nav_share')) ?></a>
            <?php if ($u['role'] === 'admin'): ?><a class="user-menu-item" href="/admin"><?= e(t('nav_admin')) ?></a><?php endif; ?>
            <form method="post" action="/logout">
              <button class="user-menu-item user-menu-logout" type="submit"><?= e(t('nav_logout')) ?></button>
            </form>
          </div>
        </details>
      <?php else: ?>
        <a class="link" href="/login"><?= e(t('nav_login')) ?></a>
        <a class="btn-primary" href="/signup"><?= e(t('nav_join')) ?></a>
      <?php endif; ?>
    </div>
  </nav>
  <main class="page"><?= $content ?></main>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
