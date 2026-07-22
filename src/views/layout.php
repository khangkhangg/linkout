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
    <div class="nav-right"><!-- auth links wired in Task 4 --></div>
  </nav>
  <main class="page"><?= $content ?></main>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
