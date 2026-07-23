<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Settings</h1>
<?php if (!empty($saved)): ?><p class="form-notice">Saved.</p><?php endif; ?>

<section class="card admin-settings-block">
  <h2 class="h-subheading">Report threshold</h2>
  <p class="muted">A story auto-hides once it has verified reports from this many distinct
    corporate domains.</p>
  <form method="post" action="/admin/settings" class="admin-filter">
    <input class="input" type="number" name="report_threshold" min="1" max="20"
           value="<?= e($report_threshold) ?>" style="max-width:120px">
    <button class="btn-primary" name="do" value="threshold" type="submit">Save</button>
  </form>
</section>

<section class="card admin-settings-block">
  <h2 class="h-subheading">Site announcement</h2>
  <p class="muted">Shown as a banner to every visitor. Leave empty to hide.</p>
  <form method="post" action="/admin/settings">
    <textarea class="input" name="announcement" rows="2" maxlength="255"
              placeholder="e.g. Scheduled maintenance Sunday 9pm."><?= e($announcement) ?></textarea>
    <button class="btn-primary" name="do" value="announcement" type="submit">Save announcement</button>
  </form>
</section>

<section class="card admin-settings-block">
  <h2 class="h-subheading">Analytics &amp; ads</h2>
  <p class="muted">Paste your IDs only — we build the official Google snippets and load them
    in the &lt;head&gt; of public pages. Leave a field empty to disable it.</p>
  <?php if (!empty($analytics_err)): ?><p class="form-error">Invalid ID format. GA looks like
    <code>G-XXXXXXX</code>; AdSense looks like <code>ca-pub-1234567890123456</code>.</p><?php endif; ?>
  <form method="post" action="/admin/settings">
    <label class="admin-field-label">Google Analytics measurement ID</label>
    <input class="input" name="ga_measurement_id" value="<?= e($ga_measurement_id) ?>"
           placeholder="G-XXXXXXX" autocomplete="off">
    <label class="admin-field-label">Google AdSense publisher ID</label>
    <input class="input" name="adsense_client" value="<?= e($adsense_client) ?>"
           placeholder="ca-pub-XXXXXXXXXXXXXXXX" autocomplete="off">
    <button class="btn-primary" name="do" value="analytics" type="submit">Save analytics &amp; ads</button>
  </form>
</section>

<section class="card admin-settings-block">
  <h2 class="h-subheading">Privacy</h2>
  <p class="muted">Erase stored reporter corporate emails from all resolved (dismissed or
    actioned) reports. Pending reports keep their email until resolved.</p>
  <form method="post" action="/admin/settings">
    <button class="btn-danger" name="do" value="purge_emails" type="submit">Purge resolved report emails</button>
  </form>
  <?php if (isset($purged)): ?><p class="muted"><?= (int)$purged ?> report(s) cleared.</p><?php endif; ?>
</section>
