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
      <input type="search" name="q" placeholder="Search companies or stories" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="nav-right">
      <?php if ($u = current_user()): ?>
        <a class="btn-primary" href="/post">Share your story</a>
        <span class="nav-handle"><?= e($u['handle']) ?></span>
        <?php if ($u['role'] === 'admin'): ?><a class="link" href="/admin">Admin</a><?php endif; ?>
        <a class="link" href="/logout">Log out</a>
      <?php else: ?>
        <a class="link" href="/login">Log in</a>
        <a class="btn-primary" href="/signup">Join</a>
      <?php endif; ?>
    </div>
  </nav>
  <main class="page"><?= $content ?></main>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
