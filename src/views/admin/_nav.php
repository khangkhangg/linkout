<nav class="admin-nav">
  <?php
  $tabs = [
      '/admin' => 'Dashboard',
      '/admin/queue' => 'Reports',
      '/admin/clusters' => 'Coordinated',
      '/admin/stories' => 'Stories',
      '/admin/comments' => 'Comments',
      '/admin/users' => 'Users',
      '/admin/companies' => 'Companies',
      '/admin/blocklist' => 'Blocklist',
      '/admin/settings' => 'Settings',
      '/admin/log' => 'Audit log',
  ];
  $cur = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
  foreach ($tabs as $href => $label): ?>
    <a class="admin-tab <?= $cur === $href ? 'admin-tab-active' : '' ?>" href="<?= $href ?>"><?= e($label) ?>
      <?php if ($href === '/admin/queue' && !empty($nav_pending)): ?><span class="admin-badge"><?= (int)$nav_pending ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
