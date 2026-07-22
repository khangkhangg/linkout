<?php $u = current_user();
$labels = ['r_leadership' => 'Leadership', 'r_culture' => 'Work culture',
    'r_benefits' => 'Comp & benefits', 'r_balance' => 'Work-life balance',
    'r_growth' => 'Career growth', 'r_exit' => 'Exit experience']; ?>
<article class="card story-full" id="story-<?= (int)$story['id'] ?>">
  <?php if ($story['status'] !== 'active'): ?>
    <p class="review-banner">This story is under review.</p>
  <?php else: ?>
  <div class="story-head">
    <div class="vote-col" data-story="<?= (int)$story['id'] ?>">
      <button class="vote-btn vote-up <?= $my_vote === 1 ? 'vote-on' : '' ?>" data-value="1"
              aria-label="Upvote">&#9650;</button>
      <span class="vote-score"><?= (int)$story['vote_score'] ?></span>
      <button class="vote-btn vote-down <?= $my_vote === -1 ? 'vote-on' : '' ?>" data-value="-1"
              aria-label="Downvote">&#9660;</button>
    </div>
    <div>
      <a class="company-chip" href="/company/<?= e($story['domain']) ?>"><?= e($story['company_name']) ?></a>
      <h1 class="h-heading"><?= e($story['title']) ?></h1>
      <p class="muted"><?= e($story['handle']) ?> · <?= e(time_ago($story['created_at'])) ?>
        <?php if ((int)$story['recommend']): ?> · <span class="rec-badge">Recommends</span><?php endif; ?></p>
    </div>
  </div>
  <div class="rating-summary">
    <?php foreach ($labels as $key => $label): ?>
    <div class="rating-row">
      <span class="rating-label"><?= e($label) ?></span>
      <span class="stars-static"><?php for ($i = 1; $i <= 5; $i++): ?><span
        class="star <?= $i <= (int)$story[$key] ? 'star-on' : '' ?>">&#9733;</span><?php endfor; ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="story-text"><?= nl2br(e($story['body'])) ?></div>
  <div class="story-actions">
    <?php if (story_editable_by($story, $u)): ?>
      <a class="link" href="/story/<?= (int)$story['id'] ?>/edit">Edit</a>
      <form method="post" action="/story/<?= (int)$story['id'] ?>/delete" class="inline-form"
            onsubmit="return confirm('Delete this story?')">
        <button class="link-btn" type="submit">Delete</button>
      </form>
    <?php endif; ?>
    <!-- Report button added in Task 11 -->
  </div>
  <?php endif; ?>
</article>

<?php if ($story['status'] === 'active'): ?>
<section id="comments" class="comments">
  <h2 class="h-subheading"><?= count($comments) ?> comments</h2>
  <?php foreach ($comments as $c): ?>
  <div class="card comment">
    <p class="muted"><?= e($c['handle']) ?> · <?= e(time_ago($c['created_at'])) ?></p>
    <p><?= nl2br(e($c['body'])) ?></p>
    <?php if ($u && (int)$c['user_id'] === (int)$u['id']
              && strtotime($c['created_at']) > time() - 86400): ?>
      <form method="post" action="/comment/<?= (int)$c['id'] ?>/delete" class="inline-form">
        <button class="link-btn" type="submit">Delete</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if ($u && $u['email_verified_at'] && $story['status'] === 'active'): ?>
  <form method="post" action="/story/<?= (int)$story['id'] ?>/comment" class="comment-form">
    <textarea class="input" name="body" rows="3" maxlength="2000" required
              placeholder="Add a comment"></textarea>
    <button class="btn-primary" type="submit">Comment</button>
  </form>
  <?php elseif (!$u): ?>
  <p class="muted"><a class="link" href="/login">Log in</a> to comment.</p>
  <?php endif; ?>
</section>
<?php endif; ?>
