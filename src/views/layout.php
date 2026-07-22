<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'LinkOut') ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
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
        <span class="nav-handle"><?= e($u['handle']) ?></span>
        <?php if ($u['role'] === 'admin'): ?><a class="link" href="/admin"><?= e(t('nav_admin')) ?></a><?php endif; ?>
        <form method="post" action="/logout" class="inline-form">
          <button class="link-btn" type="submit"><?= e(t('nav_logout')) ?></button>
        </form>
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
