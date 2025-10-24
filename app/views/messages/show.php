<?php $title = 'Conversation #' . (int)($conversation['conversation_id'] ?? 0); ?>
<?php
  // Base prefix for subfolder deployments
  $__scriptDir = str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
  $__BASE_PREFIX = ($__scriptDir && $__scriptDir !== '/') ? $__scriptDir : '';
?>
<div class="page-header">
  <a class="btn btn-sm btn-outline-dark" href="/messages">Back</a>
  <?php
    // Determine the other participant in the conversation (for profile link)
    $otherUserId = null; $otherUserName = null;
    if (!empty($participants)) {
      foreach ($participants as $p) {
        if ((int)($p['user_id'] ?? 0) !== (int)($current_user_id ?? 0)) {
          $otherUserId = (int)($p['user_id'] ?? 0);
          $otherUserName = trim(((string)($p['first_name'] ?? '')) . ' ' . ((string)($p['last_name'] ?? '')));
          break;
        }
      }
    }
  ?>
  <?php if (!empty($otherUserId)): ?>
    <a class="btn btn-sm btn-primary ms-2" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/u/<?= (int)$otherUserId ?>">View Profile<?= $otherUserName ? (': '.htmlspecialchars($otherUserName)) : '' ?></a>
  <?php endif; ?>
  </div>
<div class="card chat-card mb-3">
  <div class="card-body messages-pane">
    <?php if (empty($messages)): ?>
      <div class="text-muted">No messages yet.</div>
    <?php else: ?>
      <?php foreach ($messages as $m): ?>
        <?php $mine = ((int)($m['sender_id'] ?? 0) === (int)($current_user_id ?? 0)); ?>
        <div class="chat-row <?= $mine ? 'mine' : 'other' ?>" data-message-id="<?= (int)($m['message_id'] ?? 0) ?>">
          <?php if (!$mine): ?>
            <div class="me-1">
              <?php $pic = $m['sender_picture_url'] ?? null; ?>
              <?php if (!empty($pic)): ?>
                <img class="nx-avatar nx-avatar-sm" src="<?= htmlspecialchars($pic) ?>" alt="avatar">
              <?php else: ?>
                <div class="nx-avatar nx-avatar-sm nx-avatar-initials">👤</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="chat-bubble <?= $mine ? 'mine' : 'other' ?>">
            <div class="message-meta">From <?= htmlspecialchars(trim(((string)($m['sender_first_name'] ?? '')) . ' ' . ((string)($m['sender_last_name'] ?? ''))) ?: (string)($m['sender_email'] ?? ("user #".(string)($m['sender_id'] ?? '')))) ?> • <?= htmlspecialchars((string)($m['created_at'] ?? '')) ?></div>
            <div class="message-text"><?= nl2br(htmlspecialchars((string)($m['message_text'] ?? ''))) ?></div>
            <?php $atts = $m['attachments'] ?? []; if (!empty($atts)): ?>
              <div class="attach">
                <?php
                  $scriptDir = str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
                  $prefix = ($scriptDir && $scriptDir !== '/') ? $scriptDir : '';
                ?>
                <?php foreach ($atts as $a):
                  $mime = (string)($a['mime_type'] ?? '');
                  $url = (string)($a['file_url'] ?? '');
                  $name = (string)($a['file_name'] ?? '');
                  if (preg_match('/^https?:\/\//i', $url)) {
                    $abs = $url;
                  } else {
                    $rootRel = str_starts_with($url, '/') ? $url : ('/' . $url);
                    $abs = (strpos($rootRel, $prefix . '/') === 0) ? $rootRel : ($prefix . $rootRel);
                  }
                ?>
                  <?php if (strpos($mime, 'image/') === 0): ?>
                    <a href="<?= htmlspecialchars($abs) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($abs) ?>" alt="<?= htmlspecialchars($name) ?>"></a>
                  <?php else: ?>
                    <a class="doc-pill" href="<?= htmlspecialchars($abs) ?>" download>
                      <span class="attach-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.76 17.87a2 2 0 11-2.83-2.83l8.13-8.13"/>
                        </svg>
                      </span>
                      <span class="attach-name"><?= htmlspecialchars($name) ?></span>
                    </a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="chat-actions">
              <?php $reported = !empty($m['reported']); ?>
              <?php if (!$mine): ?>
                <?php $viewerIsAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], (int)(\Nexus\Helpers\Auth::id() ?? 0), 'manage.permissions'); ?>
                <?php if (!$viewerIsAdmin): ?>
                <a class="btn btn-sm btn-outline-warning" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/report?target_type=message&target_id=<?= (int)($m['message_id'] ?? 0) ?>">Report</a>
                <?php endif; ?>
              <?php else: ?>
                <?php if (!$reported): ?>
                  <form method="post" action="/messages/<?= (int)$conversation['conversation_id'] ?>/message/<?= (int)($m['message_id'] ?? 0) ?>/delete" class="d-inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message? This cannot be undone.');">Delete</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($mine): ?>
                <span class="small text-muted seen-indicator" data-msg="<?= (int)($m['message_id'] ?? 0) ?>"><?= ((int)($m['is_read'] ?? 0) === 1) ? '✓ Seen' : '✓ Sent' ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<form id="chatForm" method="post" action="/messages/<?= (int)$conversation['conversation_id'] ?>/send" enctype="multipart/form-data" class="chat-input">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
  <div class="chat-previews" id="chatPreviews"></div>
  <div class="chat-input-bar">
    <button type="button" class="chat-icon-btn" id="btnFile" title="Attach file" aria-label="Attach file">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.76 17.87a2 2 0 11-2.83-2.83l8.13-8.13"/>
      </svg>
    </button>
    <input type="file" name="files[]" id="fileInput" multiple hidden>
    <button type="button" class="chat-icon-btn" id="btnImage" title="Attach image" aria-label="Attach image">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/>
        <path d="M21 15l-4.5-4.5L12 15l-3-3L3 18"/>
      </svg>
    </button>
    <input type="file" accept="image/*" name="images[]" id="imageInput" multiple hidden>
    <textarea id="msgBox" class="form-control" name="message_text" rows="1" placeholder="Type a message..." style="height:38px; max-height:140px;"></textarea>
    <button id="sendBtn" class="chat-send-btn" type="submit" title="Send" aria-label="Send">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 6l16 6-16 6 5-6-5-6z"/>
      </svg>
    </button>
  </div>
  <div id="typing" class="small text-muted mt-1" style="min-height:1.2rem;"></div>
  <div id="sendStatus" class="small mt-2 nx-fade"></div>
</form>

<!-- Oversize file warning modal -->
<div class="modal fade" id="fileTooLargeModal" tabindex="-1" aria-labelledby="fileTooLargeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fileTooLargeLabel">File too large</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Each attachment must be 25 MB or less. The following file(s) were not added:</p>
        <ul id="tooLargeList" class="mb-0"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
  </div>

<?php $me = \Nexus\Helpers\Auth::user(); if (($me['role'] ?? '') === 'recruiter' && !empty($otherUserId)): ?>
  <?php $blocked = \Nexus\Helpers\Messaging::isRecruiterRepliesBlocked($GLOBALS['config'], (int)($me['user_id'] ?? 0), (int)$otherUserId); ?>
  <form method="post" action="/messages/toggle-recruiter-replies" class="mt-3">
    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <input type="hidden" name="user_id" value="<?= (int)$otherUserId ?>">
    <input type="hidden" name="blocked" value="<?= $blocked ? 0 : 1 ?>">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/messages') ?>">
    <button class="btn btn-sm <?= $blocked ? 'btn-outline-success' : 'btn-outline-danger' ?>" type="submit">
      <?= $blocked ? 'Enable replies from user' : 'Disable replies from user' ?>
    </button>
  </form>
<?php endif; ?>

<script>
  (function() {
    const pane = document.querySelector('.messages-pane');
    const convId = <?= (int)$conversation['conversation_id'] ?>;
    const BASE = (window.__BASE__ || '');
    if (pane) { pane.scrollTop = pane.scrollHeight; }
    let lastTs = (pane && pane.lastElementChild) ? pane.lastElementChild.querySelector('.message-meta')?.textContent : '';
    const myId = <?= (int)($current_user_id ?? 0) ?>;
    const form = document.getElementById('chatForm');
    const input = document.getElementById('msgBox');
    const sendBtn = document.getElementById('sendBtn');
  const sendStatus = document.getElementById('sendStatus');
  const btnFile = document.getElementById('btnFile');
  const btnImage = document.getElementById('btnImage');
  const fileInput = document.getElementById('fileInput');
  const imageInput = document.getElementById('imageInput');
  const previews = document.getElementById('chatPreviews');
  const LIMIT_MB = 25;
  const MAX_BYTES = LIMIT_MB * 1024 * 1024;
  let pendingImages = [];
  let pendingFiles = [];
  function clearAttachments(){ if (fileInput) fileInput.value=''; if (imageInput) imageInput.value=''; if (previews) previews.innerHTML=''; }
  let statusTimer = null;
    // Replace broken images with a filename link
    function absUrl(u){
      if (!u) return '';
      if (/^https?:\/\//i.test(u)) return u;
      // If it already starts with BASE, return as-is
      if (BASE && (u.startsWith(BASE + '/') || u === BASE)) return u;
      if (BASE) return BASE + (u.startsWith('/') ? u : ('/' + u));
      return u;
    }
    function attachImgFallback(container){
      if (!container) return;
      const imgs = container.querySelectorAll('.chat-bubble .attach img');
      imgs.forEach(img => {
        if (img.dataset.fallbackBound) return;
        img.dataset.fallbackBound = '1';
        img.addEventListener('error', () => {
          const raw = img.getAttribute('src') || '#';
          const url = absUrl(raw);
          const name = img.getAttribute('alt') || 'attachment';
          const a = document.createElement('a');
          a.href = url; a.target = '_blank'; a.rel = 'noopener';
          a.textContent = name;
          const wrap = document.createElement('div');
          wrap.appendChild(a);
          img.replaceWith(wrap);
        }, { once: true });
      });
    }
    function formatBytes(bytes){ if (!bytes && bytes !== 0) return ''; const sizes=['B','KB','MB','GB']; let i=0; let v=bytes; while(v>=1024 && i<sizes.length-1){ v/=1024; i++; } return (Math.round(v*10)/10)+' '+sizes[i]; }
    function showTooLarge(files){
      const list = document.getElementById('tooLargeList');
      if (list) {
        list.innerHTML='';
        (files||[]).forEach(f=>{
          const li=document.createElement('li');
          li.textContent = `${f.name} — ${formatBytes(f.size)}`;
          list.appendChild(li);
        });
      }
      try {
        if (window.bootstrap && window.bootstrap.Modal) {
          const el = document.getElementById('fileTooLargeModal');
          const modal = new window.bootstrap.Modal(el);
          modal.show();
        } else {
          alert('Some files exceed the 25 MB limit and were not added.');
        }
      } catch(_) { alert('Some files exceed the 25 MB limit and were not added.'); }
    }
    // File pickers and previews
  btnFile?.addEventListener('click', ()=> { if (fileInput) fileInput.value=''; fileInput?.click(); });
  btnImage?.addEventListener('click', ()=> { if (imageInput) imageInput.value=''; imageInput?.click(); });
    function addPreview(file){
      const isImg = file.type && file.type.startsWith('image/');
      const el = document.createElement('div');
      el.className = isImg ? 'prev' : 'file-chip';
      const close = document.createElement('button');
      close.type = 'button';
      close.className = 'remove-btn';
      close.textContent = '×';
      if (isImg) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.onload = () => URL.revokeObjectURL(img.src);
        el.appendChild(img);
        close.addEventListener('click', ()=>{
          pendingImages = pendingImages.filter(f => f !== file);
          el.remove();
        });
      } else {
        el.textContent = file.name;
        close.addEventListener('click', ()=>{
          pendingFiles = pendingFiles.filter(f => f !== file);
          el.remove();
        });
      }
      el.appendChild(close);
      previews?.appendChild(el);
    }
    fileInput?.addEventListener('change', (e)=> {
      const tooBig=[];
      for (const f of e.target.files) {
        if ((f.size||0) > MAX_BYTES) { tooBig.push(f); continue; }
        pendingFiles.push(f); addPreview(f);
      }
      if (tooBig.length) showTooLarge(tooBig);
      fileInput.value='';
    });
    imageInput?.addEventListener('change', (e)=> {
      const tooBig=[];
      for (const f of e.target.files) {
        if ((f.size||0) > MAX_BYTES) { tooBig.push(f); continue; }
        pendingImages.push(f); addPreview(f);
      }
      if (tooBig.length) showTooLarge(tooBig);
      imageInput.value='';
    });
    let sending = false;
    let es = null;
    let useSSE = false;
    let pollTimer = null;
    // Auto-grow textarea
    function autoGrow(){
      input.style.height = '38px';
      input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }
    input?.addEventListener('input', autoGrow);
    setTimeout(autoGrow, 0);
    // Enter to send, Shift+Enter for newline
    input?.addEventListener('keydown', (e)=>{
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form?.dispatchEvent(new Event('submit', {cancelable:true, bubbles:true}));
      }
    });
    function tsOfLast() {
      const times = pane.querySelectorAll('.message-meta');
      if (!times.length) return '';
      return times[times.length - 1].textContent.split('•').pop().trim();
    }
    function render(msgs) {
      for (const m of msgs) {
        const wrap = document.createElement('div');
        const mine = (m.sender_id === myId);
  wrap.className = 'chat-row ' + (mine ? 'mine' : 'other');
  wrap.dataset.messageId = String(m.message_id || '');
        const pic = m.sender_picture_url || '';
        wrap.innerHTML = `
          ${!mine ? `<div class="me-1">${pic ? `<img class=\"nx-avatar nx-avatar-sm\" src=\"${pic}\" alt=\"avatar\">` : `<div class=\"nx-avatar nx-avatar-sm nx-avatar-initials\">👤</div>`}</div>` : ''}
          <div class="chat-bubble ${mine ? 'mine' : 'other'}">
            <div class="message-meta">From ${((m.sender_first_name||'') + ' ' + (m.sender_last_name||'')).trim() || m.sender_email || ('user #'+m.sender_id)} • ${m.created_at}</div>
            <div class="message-text">${(m.message_text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}</div>
            ${Array.isArray(m.attachments) && m.attachments.length ? `<div class=\"attach\">${m.attachments.map(a => {
              const mime = (a.mime_type||'');
              const url = absUrl(a.file_url||'');
              const name = (a.file_name||'');
              if (mime.startsWith('image/')) return `<a href=\"${url}\" target=\"_blank\" rel=\"noopener\"><img src=\"${url}\" alt=\"${name}\"></a>`;
              return `<a class=\"doc-pill\" href=\"${url}\" download><span class=\"attach-icon\" aria-hidden=\"true\">`+
                `<svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">`+
                `<path d=\"M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.76 17.87a2 2 0 11-2.83-2.83l8.13-8.13\"/></svg>`+
                `</span><span class=\"attach-name\">${name}</span></a>`;
            }).join('')}</div>` : ''}
            <div class=\"chat-actions\">
              ${!mine ? `<a class=\"btn btn-sm btn-outline-warning\" href=\"${BASE}/report?target_type=message&target_id=${m.message_id}\">Report</a>` : (m.reported ? '' : `<form method=\"post\" action=\"${BASE}/messages/${convId}/message/${m.message_id}/delete\" class=\"d-inline js-del\"><input type=\"hidden\" name=\"_token\" value=\"${encodeURIComponent('<?= \Nexus\Helpers\Csrf::token() ?>')}\"><button type=\"submit\" class=\"btn btn-sm btn-outline-danger\">Delete</button></form>`)}
              ${mine ? `<span class=\"small text-muted seen-indicator\">${m.is_read ? '✓ Seen' : '✓ Sent'}</span>` : ''}
            </div>
          </div>
        `;
        pane.appendChild(wrap);
        attachImgFallback(wrap);
      }
      pane.scrollTop = pane.scrollHeight;
    }
    async function poll() {
      if (useSSE) return; // don't poll when SSE is active
      try {
        const since = tsOfLast();
  const res = await fetch(`${BASE}/messages/${convId}/poll` + (since ? `?since=${encodeURIComponent(since)}` : ''));
        if (res.ok) {
          const data = await res.json();
          if (Array.isArray(data) && data.length) render(data);
        }
      } catch (e) { /* ignore */ }
      pollTimer = setTimeout(poll, 3000);
    }
    function startSSE(){
      try {
        const since = encodeURIComponent(tsOfLast());
        es = new EventSource(`${BASE}/messages/${convId}/stream` + (since ? `?since=${since}` : ''));
        useSSE = true;
        es.onmessage = (ev) => {
          try { const data = JSON.parse(ev.data); if (Array.isArray(data) && data.length) render(data); } catch(e){}
        };
        es.addEventListener('ping', ()=>{});
        es.onerror = () => { try { es.close(); } catch(_){} es = null; useSSE = false; setTimeout(poll, 2000); };
      } catch (e) {
        setTimeout(poll, 2000);
      }
    }
    if (!!window.EventSource) startSSE(); else setTimeout(poll, 3000);

    // Pause background activity when tab hidden and cleanup on unload to speed back/refresh
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        if (es) { try { es.close(); } catch(_){} es = null; useSSE = false; }
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
      } else {
        if (!!window.EventSource) startSSE(); else pollTimer = setTimeout(poll, 1200);
      }
    });
    window.addEventListener('beforeunload', () => {
      if (es) { try { es.close(); } catch(_){} es = null; }
      if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
    });

    // Infinite scroll: load older messages when scrolled to top
    let loading = false;
    pane.addEventListener('scroll', async () => {
      if (loading) return;
      if (pane.scrollTop <= 0) {
        loading = true;
  const firstMeta = pane.querySelector('.chat-row .message-meta');
        const before = firstMeta ? firstMeta.textContent.split('•').pop().trim() : '';
        try {
          const res = await fetch(`${BASE}/messages/${convId}/history` + (before ? `?before=${encodeURIComponent(before)}&limit=50` : '?limit=50'));
          if (res.ok) {
            const older = await res.json();
            if (Array.isArray(older) && older.length) {
              const oldHeight = pane.scrollHeight;
              const frag = document.createDocumentFragment();
              // older are newest-first; prepend in reverse to keep order
              for (let i = older.length - 1; i >= 0; i--) {
                const m = older[i];
                const mine = (m.sender_id === myId);
                const wrap = document.createElement('div');
                wrap.className = 'chat-row ' + (mine ? 'mine' : 'other');
                const pic = m.sender_picture_url || '';
                wrap.innerHTML = `
                  ${!mine ? `<div class="me-1">${pic ? `<img class=\"nx-avatar nx-avatar-sm\" src=\"${pic}\" alt=\"avatar\">` : `<div class=\"nx-avatar nx-avatar-sm nx-avatar-initials\">👤</div>`}</div>` : ''}
                  <div class="chat-bubble ${mine ? 'mine' : 'other'}">
                    <div class="message-meta">From ${((m.sender_first_name||'') + ' ' + (m.sender_last_name||'')).trim() || m.sender_email || ('user #'+m.sender_id)} • ${m.created_at}</div>
                    <div class="message-text">${(m.message_text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}</div>
                    ${Array.isArray(m.attachments) && m.attachments.length ? `<div class=\"attach\">${m.attachments.map(a => {
                      const mime = (a.mime_type||'');
                      const url = absUrl(a.file_url||'');
                      const name = (a.file_name||'');
                      if (mime.startsWith('image/')) return `<a href=\"${url}\" target=\"_blank\" rel=\"noopener\"><img src=\"${url}\" alt=\"${name}\"></a>`;
                      return `<a class=\"doc-pill\" href=\"${url}\" download><span class=\"attach-icon\" aria-hidden=\"true\">`+
                        `<svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">`+
                        `<path d=\"M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66L9.76 17.87a2 2 0 11-2.83-2.83l8.13-8.13\"/></svg>`+
                        `</span><span class=\"attach-name\">${name}</span></a>`;
                    }).join('')}</div>` : ''}
                  </div>
                `;
                frag.insertBefore(wrap, frag.firstChild);
              }
              pane.insertBefore(frag, pane.firstChild);
              attachImgFallback(pane);
              const newHeight = pane.scrollHeight;
              pane.scrollTop = newHeight - oldHeight; // maintain position
            }
          }
        } catch (e) {}
        loading = false;
      }
    });

    // Intercept send form to post via fetch and render instantly
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (sending) return;
  const text = (input?.value || '').trim();
  const hasFiles = (pendingFiles && pendingFiles.length ? pendingFiles.length : 0) + (pendingImages && pendingImages.length ? pendingImages.length : 0) > 0;
        if (!text && !hasFiles) return;
        sending = true;
  if (sendBtn) { sendBtn.disabled = true; sendBtn.setAttribute('aria-busy','true'); }
        if (sendStatus) { sendStatus.textContent = ''; sendStatus.className = 'small mt-2 text-muted'; }
        // On some local servers, long-lived SSE can block other requests; close it while sending
        if (es) { try { es.close(); } catch(_){} es = null; useSSE = false; }
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
        try {
          const fd = new FormData(form);
          // Build files from pending arrays
          for (const f of (pendingFiles||[])) fd.append('files[]', f, f.name||'file');
          for (const f of (pendingImages||[])) fd.append('images[]', f, f.name||'image');
          const res = await fetch(`${BASE}/messages/${convId}/send`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
          if (res.ok) {
            const data = await res.json();
            if (data && data.ok && data.message) {
              render([data.message]);
              input.value = '';
              autoGrow();
              clearAttachments();
              pendingFiles = []; pendingImages = [];
              if (sendStatus) {
                sendStatus.textContent = 'Sent';
                sendStatus.className = 'small mt-2 text-success nx-fade';
                if (statusTimer) { clearTimeout(statusTimer); statusTimer = null; }
                statusTimer = setTimeout(() => {
                  sendStatus.classList.add('nx-fade-hide');
                  setTimeout(() => { sendStatus.textContent = ''; sendStatus.classList.remove('nx-fade-hide'); }, 400);
                }, 1500);
              }
            } else {
              // Fallback: do nothing; SSE will deliver
            }
          } else {
            const txt = await res.text();
            if (sendStatus) { sendStatus.textContent = txt || 'Failed to send'; sendStatus.className = 'small mt-2 text-danger nx-fade'; sendStatus.classList.remove('nx-fade-hide'); }
          }
        } catch (err) {
          if (sendStatus) { sendStatus.textContent = 'Network error'; sendStatus.className = 'small mt-2 text-danger nx-fade'; sendStatus.classList.remove('nx-fade-hide'); }
        }
        sending = false;
  if (sendBtn) { sendBtn.disabled = false; sendBtn.removeAttribute('aria-busy'); }
        // Restart SSE if supported; otherwise resume polling
        if (!!window.EventSource) { setTimeout(startSSE, 200); } else { pollTimer = setTimeout(poll, 1500); }
      });
    }

    // Delegate delete forms (dynamic) to AJAX and update UI without full refresh
    pane?.addEventListener('submit', async (e) => {
      const t = e.target;
      if (!(t instanceof HTMLFormElement)) return;
      if (!t.classList.contains('js-del')) return;
      e.preventDefault();
      const ok = confirm('Delete this message? This cannot be undone.');
      if (!ok) return;
      try {
        const fd = new FormData(t);
        const res = await fetch(t.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
        if (res.ok) {
          // Remove bubble
          const bubble = t.closest('.chat-row');
          if (bubble) bubble.remove();
        } else {
          alert(await res.text() || 'Delete failed');
        }
      } catch (err) { alert('Network error'); }
    });

    // Drag & drop support onto input bar
    const inputBar = document.querySelector('.chat-input-bar');
    function cancel(e){ e.preventDefault(); e.stopPropagation(); }
    ['dragenter','dragover','dragleave','drop'].forEach(ev => inputBar?.addEventListener(ev, cancel));
    inputBar?.addEventListener('drop', (e)=>{
      const dt = e.dataTransfer; if (!dt) return;
      const files = dt.files; if (!files || !files.length) return;
      // Decide whether image or file input
      const imgs = []; const rest = []; const tooBig=[];
      for (const f of files){ if ((f.size||0) > MAX_BYTES) { tooBig.push(f); continue; } if ((f.type||'').startsWith('image/')) imgs.push(f); else rest.push(f); }
      for (const f of rest) { pendingFiles.push(f); addPreview(f); }
      for (const f of imgs) { pendingImages.push(f); addPreview(f); }
      if (tooBig.length) showTooLarge(tooBig);
    });
  })();
</script>
<script>
  (function(){
    const box = document.getElementById('msgBox');
    const typingEl = document.getElementById('typing');
    const convId = <?= (int)$conversation['conversation_id'] ?>;
    const BASE = (window.__BASE__ || '');
    let lastSent = 0;
    box?.addEventListener('input', () => {
      const now = Date.now();
      if (now - lastSent < 2000) return; // throttle
      lastSent = now;
  fetch(`${BASE}/messages/${convId}/typing`, { method: 'POST' });
    });
    async function checkTyping(){
      try {
  const res = await fetch(`${BASE}/messages/${convId}/typing`);
        if (res.ok) {
          const arr = await res.json();
          if (Array.isArray(arr) && arr.length) {
            const names = arr.map(x => (x.first_name||'Someone') + (x.last_name?(' '+x.last_name):''));
            typingEl.textContent = names.join(', ') + ' is typing...';
          } else {
            typingEl.textContent = '';
          }
        }
      } catch (e) {}
      setTimeout(checkTyping, 2000);
    }
    setTimeout(checkTyping, 2000);
  })();
</script>
<script>
  // Seen status refresher for last outgoing message
  (function(){
    const BASE = (window.__BASE__ || '');
    const convId = <?= (int)$conversation['conversation_id'] ?>;
    // Periodically fetch reported message IDs and hide delete buttons live
    async function refreshReported(){
      try {
        const res = await fetch(`${BASE}/messages/${convId}/reported`);
        if (res.ok) {
          const ids = await res.json();
          if (Array.isArray(ids) && ids.length) {
            ids.forEach(id => {
              const row = document.querySelector(`.messages-pane .chat-row[data-message-id="${id}"]`);
              if (row) {
                const delForm = row.querySelector('form.js-del');
                if (delForm) delForm.remove();
              }
            });
          }
        }
      } catch(e){}
      setTimeout(refreshReported, 4000);
    }
    setTimeout(refreshReported, 1500);
    async function refreshSeen(){
      try {
        const res = await fetch(`${BASE}/messages/${convId}/seen`);
        if (res.ok) {
          const data = await res.json();
          if (data && typeof data.last_mine_read !== 'undefined') {
            // update the last .seen-indicator in pane
            const indicators = document.querySelectorAll('.messages-pane .seen-indicator');
            if (indicators.length) {
              indicators[indicators.length - 1].textContent = data.last_mine_read ? '✓ Seen' : '✓ Sent';
            }
          }
        }
      } catch(e){}
      setTimeout(refreshSeen, 3000);
    }
    setTimeout(refreshSeen, 1500);
  })();
</script>
