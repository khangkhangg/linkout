<section class="post-form">
  <h1 class="h-heading">Share your story</h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="<?= $editing ? '/story/' . (int)$story['id'] . '/edit' : '/post' ?>">
    <?php if (!$editing): ?>
    <div class="field">
      <label>Company</label>
      <input class="input" id="company-name" name="company_name" placeholder="Company name"
             autocomplete="off" required value="<?= e($_POST['company_name'] ?? '') ?>">
      <div id="company-suggest" class="suggest"></div>
      <input class="input" id="company-domain" name="company_domain"
             placeholder="Company website (e.g. companyx.com)" required
             value="<?= e($_POST['company_domain'] ?? '') ?>">
    </div>
    <?php endif; ?>
    <input class="input" name="title" placeholder="Title" maxlength="200" required
           value="<?= e($editing ? $story['title'] : ($_POST['title'] ?? '')) ?>">
    <textarea class="input" name="body" rows="10" maxlength="10000" required
              placeholder="What happened? What should others know?"><?=
              e($editing ? $story['body'] : ($_POST['body'] ?? '')) ?></textarea>
    <div class="rating-grid">
      <?php $labels = ['r_leadership' => 'Leadership', 'r_culture' => 'Work culture',
          'r_benefits' => 'Comp & benefits', 'r_balance' => 'Work-life balance',
          'r_growth' => 'Career growth', 'r_exit' => 'Exit experience'];
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
      I would recommend working here
    </label>
    <button class="btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Publish anonymously' ?></button>
  </form>
</section>
