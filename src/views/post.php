<section class="post-form">
  <h1 class="h-heading"><?= e(t('post_title')) ?></h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="<?= $editing ? '/story/' . (int)$story['id'] . '/edit' : '/post' ?>">
    <?php if (!$editing): ?>
    <div class="field">
      <label><?= e(t('post_company')) ?></label>
      <input class="input" id="company-name" name="company_name" placeholder="<?= e(t('post_company_name')) ?>"
             autocomplete="off" required value="<?= e($_POST['company_name'] ?? '') ?>">
      <div id="company-suggest" class="suggest"></div>
      <input class="input" id="company-domain" name="company_domain"
             placeholder="<?= e(t('post_company_domain')) ?>" required
             value="<?= e($_POST['company_domain'] ?? '') ?>">
    </div>
    <?php endif; ?>
    <input class="input" name="title" placeholder="<?= e(t('post_story_title')) ?>" maxlength="200" required
           value="<?= e($editing ? $story['title'] : ($_POST['title'] ?? '')) ?>">
    <textarea class="input" name="body" rows="10" maxlength="10000" required
              placeholder="<?= e(t('post_body_placeholder')) ?>"><?=
              e($editing ? $story['body'] : ($_POST['body'] ?? '')) ?></textarea>
    <div class="rating-grid">
      <?php $labels = array_combine(RATING_KEYS, array_map('t', RATING_KEYS));
      foreach ($labels as $key => $label): $cur = (int)($editing ? $story[$key] : ($_POST[$key] ?? 0)); ?>
      <div class="rating-row">
        <span class="rating-label"><?= e($label) ?></span>
        <span class="stars" data-input="<?= $key ?>">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <label class="star"><input type="radio" name="<?= $key ?>" value="<?= $i ?>"
                 <?= $cur === $i ? 'checked' : '' ?> required><span>★</span></label>
          <?php endfor; ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <label class="recommend-row">
      <input type="checkbox" name="recommend" value="1"
             <?= ($editing ? $story['recommend'] : ($_POST['recommend'] ?? 0)) ? 'checked' : '' ?>>
      <?= e(t('post_recommend')) ?>
    </label>
    <button class="btn-primary" type="submit"><?= e($editing ? t('post_save') : t('post_publish')) ?></button>
  </form>
</section>
