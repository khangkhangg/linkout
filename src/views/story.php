<?php $u = current_user();
$labels = array_combine(RATING_KEYS, array_map('t', RATING_KEYS)); ?>
<article class="card story-full" id="story-<?= (int)$story['id'] ?>">
  <?php if ($story['status'] !== 'active'): ?>
    <p class="review-banner"><?= e(t('under_review')) ?></p>
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
        <?php if ((int)$story['recommend']): ?> · <span class="rec-badge"><?= e(t('recommends')) ?></span><?php endif; ?></p>
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
      <a class="link" href="/story/<?= (int)$story['id'] ?>/edit"><?= e(t('edit')) ?></a>
      <form method="post" action="/story/<?= (int)$story['id'] ?>/delete" class="inline-form"
            onsubmit="return confirm('<?= e(t('delete_confirm')) ?>')">
        <button class="link-btn" type="submit"><?= e(t('delete')) ?></button>
      </form>
    <?php endif; ?>
    <?php if ($u && $u['email_verified_at'] && (int)$story['user_id'] !== (int)$u['id']): ?>
      <button class="link-btn" id="report-open" data-story="<?= (int)$story['id'] ?>"><?= e(t('report')) ?></button>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</article>

<?php if ($story['status'] === 'active'): ?>
<section id="comments" class="comments">
  <h2 class="h-subheading"><?= count($comments) ?> <?= e(t('comments')) ?></h2>
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
              placeholder="<?= e(t('comment_placeholder')) ?>"></textarea>
    <button class="btn-primary" type="submit"><?= e(t('comment_submit')) ?></button>
  </form>
  <?php elseif (!$u): ?>
  <p class="muted"><a class="link" href="/login"><?= e(t('comment_login')) ?></a></p>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="modal-backdrop" id="report-modal" hidden>
  <div class="modal glass-card">
    <h3 class="h-subheading"><?= e(t('report_title')) ?></h3>
    <div data-step="1">
      <select class="input" id="report-reason">
        <option value="false_info"><?= e(t('report_reason_false_info')) ?></option>
        <option value="doxxing"><?= e(t('report_reason_doxxing')) ?></option>
        <option value="harassment"><?= e(t('report_reason_harassment')) ?></option>
        <option value="spam"><?= e(t('report_reason_spam')) ?></option>
        <option value="other"><?= e(t('report_reason_other')) ?></option>
      </select>
      <textarea class="input" id="report-text" rows="2" placeholder="<?= e(t('report_details')) ?>"></textarea>
      <input class="input" id="report-email" type="email"
             placeholder="<?= e(t('report_email_placeholder')) ?>">
      <p class="muted"><?= e(t('report_email_note')) ?></p>
      <button class="btn-primary" id="report-send"><?= e(t('report_send_code')) ?></button>
    </div>
    <div data-step="2" hidden>
      <input class="input" id="report-code" inputmode="numeric" maxlength="6"
             placeholder="<?= e(t('report_code_placeholder')) ?>">
      <button class="btn-primary" id="report-confirm"><?= e(t('report_confirm')) ?></button>
    </div>
    <p class="form-error" id="report-error" hidden></p>
    <p class="form-notice" id="report-done" hidden><?= e(t('report_done')) ?></p>
    <button class="link-btn" id="report-close"><?= e(t('close')) ?></button>
  </div>
</div>
