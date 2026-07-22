<?php $dims = ['avg_leadership' => t('r_leadership'), 'avg_culture' => t('r_culture'),
    'avg_benefits' => t('r_benefits'), 'avg_balance' => t('r_balance'),
    'avg_growth' => t('r_growth'), 'avg_exit' => t('r_exit')]; ?>
<header class="company-head">
  <h1 class="h-heading-lg"><?= e($company['name']) ?></h1>
  <p class="muted"><?= e($company['domain']) ?></p>
</header>
<?php if ($agg['story_count'] === 0): ?>
  <p class="muted">No stories yet about this company.</p>
<?php else: ?>
<section class="card agg-card">
  <div class="agg-top">
    <span class="agg-count"><?= (int)$agg['story_count'] ?> stories</span>
    <span class="agg-rec"><?= number_format((float)$agg['recommend_pct'], 0) ?>% recommend</span>
  </div>
  <?php foreach ($dims as $key => $label): $v = (float)$agg[$key]; ?>
  <div class="agg-row">
    <span class="rating-label"><?= e($label) ?></span>
    <span class="agg-bar"><span class="agg-fill" style="width: <?= $v / 5 * 100 ?>%"></span></span>
    <span class="agg-num"><?= number_format($v, 1) ?></span>
  </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="company-stories">
  <?php foreach ($stories as $s):
      $avg = round(array_sum(array_map(fn($k) => (int)$s[$k], RATING_KEYS)) / 6, 1); ?>
  <article class="card story-card">
    <div class="story-body">
      <div class="story-meta">
        <span class="rating-badge">&#9733; <?= $avg ?></span>
        <?php if ((int)$s['recommend']): ?><span class="rec-badge"><?= e(t('recommends')) ?></span><?php endif; ?>
      </div>
      <h2 class="story-title"><a href="/story/<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a></h2>
      <p class="story-excerpt"><?= e(mb_substr($s['body'], 0, 220)) ?></p>
      <p class="muted"><?= e($s['handle']) ?> · <?= e(time_ago($s['created_at'])) ?> ·
        <?= (int)$s['vote_score'] ?> points · <?= (int)$s['comment_count'] ?> <?= e(t('comments')) ?></p>
    </div>
  </article>
  <?php endforeach; ?>
</section>
