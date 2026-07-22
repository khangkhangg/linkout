<?php if (($tab ?? 'new') === 'new' && ($page ?? 1) === 1): ?>
<header class="hero">
  <h1 class="hero-title"><?= t('hero_title') ?></h1>
  <p class="hero-sub"><?= e(t('hero_sub')) ?></p>
</header>
<?php endif; ?>
<div class="feed-layout">
  <div class="feed-main">
    <nav class="feed-tabs">
      <?php foreach (['new' => t('tab_new'), 'trending' => t('tab_trending'), 'top' => t('tab_top')] as $k => $label): ?>
      <a class="tab <?= $tab === $k ? 'tab-active' : '' ?>" href="/?tab=<?= $k ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if (!$stories): ?>
      <p class="muted"><?= e(t('feed_empty')) ?></p>
    <?php endif; ?>
    <?php foreach ($stories as $s):
        $avg = round(array_sum(array_map(fn($k) => (int)$s[$k], RATING_KEYS)) / 6, 1); ?>
    <article class="card story-card" id="story-<?= (int)$s['id'] ?>">
      <div class="vote-col" data-story="<?= (int)$s['id'] ?>">
        <button class="vote-btn vote-up <?= (int)$s['my_vote'] === 1 ? 'vote-on' : '' ?>"
                data-value="1" aria-label="Upvote">&#9650;</button>
        <span class="vote-score"><?= (int)$s['vote_score'] ?></span>
        <button class="vote-btn vote-down <?= (int)$s['my_vote'] === -1 ? 'vote-on' : '' ?>"
                data-value="-1" aria-label="Downvote">&#9660;</button>
      </div>
      <div class="story-body">
        <div class="story-meta">
          <a class="company-chip" href="/company/<?= e($s['domain']) ?>"><?= e($s['company_name']) ?></a>
          <span class="rating-badge">&#9733; <?= $avg ?></span>
          <?php if ((int)$s['recommend']): ?><span class="rec-badge"><?= e(t('recommends')) ?></span><?php endif; ?>
        </div>
        <h2 class="story-title"><a href="/story/<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a></h2>
        <p class="story-excerpt"><?= e(mb_substr($s['body'], 0, 220)) ?><?= mb_strlen($s['body']) > 220 ? '…' : '' ?></p>
        <div class="story-foot">
          <span class="muted"><?= e($s['handle']) ?> · <?= e(time_ago($s['created_at'])) ?></span>
          <a class="link" href="/story/<?= (int)$s['id'] ?>#comments"><?= (int)$s['comment_count'] ?> <?= e(t('comments')) ?></a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
    <nav class="pager">
      <?php if (($page ?? 1) > 1): ?><a class="link" href="/?tab=<?= e($tab) ?>&page=<?= $page - 1 ?>"><?= e(t('pager_newer')) ?></a><?php endif; ?>
      <?php if (count($stories) === 20): ?><a class="link" href="/?tab=<?= e($tab) ?>&page=<?= $page + 1 ?>"><?= e(t('pager_older')) ?></a><?php endif; ?>
    </nav>
  </div>
  <aside class="rails">
    <section class="rail rail-trending">
      <h3 class="rail-title"><?= e(t('rail_trending')) ?></h3>
      <?php foreach ($rails['trending'] as $r): ?>
        <a class="rail-item" href="/story/<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a>
      <?php endforeach; ?>
    </section>
    <section class="rail rail-liked">
      <h3 class="rail-title"><?= e(t('rail_liked')) ?></h3>
      <?php foreach ($rails['liked'] as $r): ?>
        <a class="rail-item" href="/story/<?= (int)$r['id'] ?>"><?= e($r['title']) ?>
          <span class="muted">+<?= (int)$r['vote_score'] ?></span></a>
      <?php endforeach; ?>
    </section>
    <section class="rail rail-rated">
      <h3 class="rail-title"><?= e(t('rail_rated')) ?></h3>
      <?php foreach ($rails['rated'] as $r): ?>
        <a class="rail-item" href="/company/<?= e($r['domain']) ?>"><?= e($r['name']) ?>
          <span class="muted">&#9733; <?= number_format((float)$r['bayes_score'], 1) ?></span></a>
      <?php endforeach; ?>
    </section>
  </aside>
</div>
