<?php
  $title = 'Leaderboards | UIU NEXUS';
  ob_start();
?>
<div class="row">
  <div class="col-lg-12">
    <h2 class="mb-3">Leaderboards</h2>
    <ul class="nav nav-tabs" id="leaderTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="rep-tab" data-bs-toggle="tab" data-bs-target="#rep" type="button" role="tab">Reputation</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="coins-tab" data-bs-toggle="tab" data-bs-target="#coins" type="button" role="tab">Coins</button>
      </li>
    </ul>
    <div class="tab-content mt-3" id="leaderTabsContent">
      <div class="tab-pane fade show active" id="rep" role="tabpanel" aria-labelledby="rep-tab">
        <ul class="nav nav-pills mb-3">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#rep-weekly">Weekly</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#rep-monthly">Monthly</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#rep-all">All-time</a></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="rep-weekly">
            <div class="row">
              <?php foreach (['student','alumni'] as $role): ?>
              <div class="col-md-4 mb-3">
                <div class="card"><div class="card-body">
                  <h5 class="card-title text-capitalize"><?= htmlspecialchars($role) ?> (Weekly)</h5>
                  <?php $rows = $weekly[$role] ?? []; if (!$rows): ?>
                    <div class="text-muted">No entries.</div>
                  <?php else: ?>
                  <ol class="mb-0">
                    <?php foreach ($rows as $row): ?>
                      <?php
                        $uid = (int)$row['user_id'];
                        $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
                        $name = '';
                        try {
                          $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS label FROM users u LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE u.user_id = :u');
                          $st->execute([':u' => $uid]);
                          $name = (string)($st->fetchColumn() ?: '');
                        } catch (\Throwable $e) {}
                        if ($name === '') { $name = 'User ' . $uid; }
                      ?>
                      <li>#<?= htmlspecialchars((string)$row['rank']) ?> — <a href="/u/<?= $uid ?>"><?= htmlspecialchars($name) ?></a> — <?= htmlspecialchars((string)$row['score']) ?> pts</li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="mt-2"><button class="btn btn-sm btn-outline-secondary lb-more" data-type="rep" data-period="weekly" data-role="<?= htmlspecialchars($role) ?>" data-page="1">View more</button></div>
                  <?php endif; ?>
                </div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="tab-pane fade" id="rep-monthly">
            <div class="row">
              <?php foreach (['student','alumni'] as $role): ?>
              <div class="col-md-4 mb-3">
                <div class="card"><div class="card-body">
                  <h5 class="card-title text-capitalize"><?= htmlspecialchars($role) ?> (Monthly)</h5>
                  <?php $rows = $monthly[$role] ?? []; if (!$rows): ?>
                    <div class="text-muted">No entries.</div>
                  <?php else: ?>
                  <ol class="mb-0">
                    <?php foreach ($rows as $row): ?>
                      <?php
                        $uid = (int)$row['user_id'];
                        $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
                        $name = '';
                        try {
                          $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS label FROM users u LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE u.user_id = :u');
                          $st->execute([':u' => $uid]);
                          $name = (string)($st->fetchColumn() ?: '');
                        } catch (\Throwable $e) {}
                        if ($name === '') { $name = 'User ' . $uid; }
                      ?>
                      <li>#<?= htmlspecialchars((string)$row['rank']) ?> — <a href="/u/<?= $uid ?>"><?= htmlspecialchars($name) ?></a> — <?= htmlspecialchars((string)$row['score']) ?> pts</li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="mt-2"><button class="btn btn-sm btn-outline-secondary lb-more" data-type="rep" data-period="monthly" data-role="<?= htmlspecialchars($role) ?>" data-page="1">View more</button></div>
                  <?php endif; ?>
                </div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="tab-pane fade" id="rep-all">
            <div class="row">
              <?php foreach (['student','alumni'] as $role): ?>
              <div class="col-md-4 mb-3">
                <div class="card"><div class="card-body">
                  <h5 class="card-title text-capitalize"><?= htmlspecialchars($role) ?> (All-time)</h5>
                  <?php $rows = $all[$role] ?? []; if (!$rows): ?>
                    <div class="text-muted">No entries.</div>
                  <?php else: ?>
                  <ol class="mb-0">
                    <?php foreach ($rows as $row): ?>
                      <?php
                        $uid = (int)$row['user_id'];
                        $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
                        $name = '';
                        try {
                          $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS label FROM users u LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE u.user_id = :u');
                          $st->execute([':u' => $uid]);
                          $name = (string)($st->fetchColumn() ?: '');
                        } catch (\Throwable $e) {}
                        if ($name === '') { $name = 'User ' . $uid; }
                      ?>
                      <li>#<?= htmlspecialchars((string)$row['rank']) ?> — <a href="/u/<?= $uid ?>"><?= htmlspecialchars($name) ?></a> — <?= htmlspecialchars((string)$row['score']) ?> pts</li>
                    <?php endforeach; ?>
                  </ol>
                  <?php endif; ?>
                </div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="coins" role="tabpanel" aria-labelledby="coins-tab">
        <ul class="nav nav-pills mb-3">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#coins-daily">Today</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#coins-weekly">Weekly</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#coins-monthly">Monthly</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#coins-all">All-time</a></li>
        </ul>
        <div class="tab-content">
          <?php
            $coinsSections = [
              'coins-daily' => ['label' => 'Today', 'key' => 'daily'],
              'coins-weekly' => ['label' => 'Weekly', 'key' => 'weekly'],
              'coins-monthly' => ['label' => 'Monthly', 'key' => 'monthly'],
              'coins-all' => ['label' => 'All-time', 'key' => 'all'],
            ];
            $first = true;
          ?>
          <?php foreach ($coinsSections as $paneId => $meta): ?>
          <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="<?= $paneId ?>">
            <?php $first = false; ?>
            <div class="row">
              <?php foreach (['student','alumni'] as $role): ?>
              <div class="col-md-6 mb-3">
                <div class="card"><div class="card-body">
                  <h5 class="card-title text-capitalize"><?= htmlspecialchars($role) ?> — Top Earners (<?= htmlspecialchars($meta['label']) ?>)</h5>
                  <?php $rows = $coins[$meta['key']]['earned'][$role] ?? []; if (!$rows): ?>
                    <div class="text-muted">No entries.</div>
                  <?php else: ?>
                  <ol class="mb-3">
                    <?php foreach ($rows as $idx => $row): ?>
                      <?php
                        $uid = (int)$row['user_id'];
                        $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
                        $name = '';
                        try {
                          $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS label FROM users u LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE u.user_id = :u');
                          $st->execute([':u' => $uid]);
                          $name = (string)($st->fetchColumn() ?: '');
                        } catch (\Throwable $e) {}
                        if ($name === '') { $name = 'User ' . $uid; }
                      ?>
                      <li>#<?= $idx+1 ?> — <a href="/u/<?= $uid ?>"><?= htmlspecialchars($name) ?></a> — <?= htmlspecialchars((string)$row['score']) ?> coins</li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="mt-2"><button class="btn btn-sm btn-outline-secondary lb-more" data-type="coins" data-kind="earned" data-period="<?= htmlspecialchars($meta['key']) ?>" data-role="<?= htmlspecialchars($role) ?>" data-page="1">View more</button></div>
                  <?php endif; ?>

                  <h5 class="card-title text-capitalize mt-3"><?= htmlspecialchars($role) ?> — Top Spenders (<?= htmlspecialchars($meta['label']) ?>)</h5>
                  <?php $rows = $coins[$meta['key']]['spent'][$role] ?? []; if (!$rows): ?>
                    <div class="text-muted">No entries.</div>
                  <?php else: ?>
                  <ol class="mb-0">
                    <?php foreach ($rows as $idx => $row): ?>
                      <?php
                        $uid = (int)$row['user_id'];
                        $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
                        $name = '';
                        try {
                          $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS label FROM users u LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE u.user_id = :u');
                          $st->execute([':u' => $uid]);
                          $name = (string)($st->fetchColumn() ?: '');
                        } catch (\Throwable $e) {}
                        if ($name === '') { $name = 'User ' . $uid; }
                      ?>
                      <li>#<?= $idx+1 ?> — <a href="/u/<?= $uid ?>"><?= htmlspecialchars($name) ?></a> — <?= htmlspecialchars((string)$row['score']) ?> coins</li>
                    <?php endforeach; ?>
                  </ol>
                  <div class="mt-2"><button class="btn btn-sm btn-outline-secondary lb-more" data-type="coins" data-kind="spent" data-period="<?= htmlspecialchars($meta['key']) ?>" data-role="<?= htmlspecialchars($role) ?>" data-page="1">View more</button></div>
                  <?php endif; ?>
                </div></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
  $content = ob_get_clean();
  $content .= "\n<script>\n(function(){\n  function handle(btn){\n    var type = btn.getAttribute('data-type') || 'rep';\n    var period = btn.getAttribute('data-period') || 'weekly';\n    var role = btn.getAttribute('data-role') || 'student';\n    var page = parseInt(btn.getAttribute('data-page')||'1',10) + 1;\n    var per = 10;\n    var params = new URLSearchParams({ type:type, period:period, role:role, page:String(page), per_page:String(per) });\n    if (type === 'coins') { params.set('kind', btn.getAttribute('data-kind') || 'earned'); }\n    btn.disabled = true; btn.textContent = 'Loading...';\n    fetch('/leaderboards/page?' + params.toString())\n      .then(function(r){ return r.json(); })\n      .then(function(j){\n        if (!j || !Array.isArray(j.rows)) return;\n        var card = btn.closest('.card-body');\n        var ol = card.querySelector('ol');\n        if (!ol) { ol = document.createElement('ol'); ol.className='mb-0'; card.insertBefore(ol, btn.parentElement); }\n        j.rows.forEach(function(row){\n          var li = document.createElement('li');\n          var name = row.name || ('User ' + row.user_id);\n          li.innerHTML = '#' + row.rank + ' — ' + '<a href=\"/u/' + row.user_id + '\">' + name.replace(/</g,'&lt;') + '</a>' + ' — ' + row.score + (type==='rep'?' pts':' coins');\n          ol.appendChild(li);\n        });\n        if (j.hasNext) { btn.disabled = false; btn.textContent = 'View more'; btn.setAttribute('data-page', String(j.page)); }\n        else { btn.textContent = 'No more'; btn.disabled = true; }\n      })\n      .catch(function(){ btn.disabled = false; btn.textContent = 'View more'; });\n  }\n  document.addEventListener('click', function(e){\n    var t = e.target;\n    if (t && t.classList && t.classList.contains('lb-more')) {\n      e.preventDefault();\n      handle(t);\n    }\n  });\n})();\n</script>\n";
  include __DIR__ . '/../layouts/main.php';
?>
