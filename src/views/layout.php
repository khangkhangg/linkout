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
    <a class="nav-logo" href="/">LinkOut</a>
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
        <a class="link" href="/logout"><?= e(t('nav_logout')) ?></a>
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
