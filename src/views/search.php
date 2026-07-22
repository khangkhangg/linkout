<h1 class="h-heading">Search: <?= e($q) ?></h1>
<?php if ($companies): ?>
<section class="search-companies">
  <h2 class="h-subheading">Companies</h2>
  <?php foreach ($companies as $c): ?>
    <a class="company-chip" href="/company/<?= e($c['domain']) ?>"><?= e($c['name']) ?>
      <span class="muted"><?= e($c['domain']) ?></span></a>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="search-stories">
  <h2 class="h-subheading">Stories</h2>
  <?php if (!$stories): ?><p class="muted">No matching stories.</p><?php endif; ?>
  <?php foreach ($stories as $s): ?>
  <article class="card story-card">
    <div class="story-body">
      <a class="company-chip" href="/company/<?= e($s['domain']) ?>"><?= e($s['company_name']) ?></a>
      <h3 class="story-title"><a href="/story/<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a></h3>
      <p class="story-excerpt"><?= e(mb_substr($s['body'], 0, 220)) ?></p>
    </div>
  </article>
  <?php endforeach; ?>
</section>
