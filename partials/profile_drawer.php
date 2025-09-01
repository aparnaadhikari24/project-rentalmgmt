<?php if (current_user_id()): ?>
<div class="drawer-backdrop" id="profileBackdrop"></div>
<aside class="drawer" id="profileDrawer">
  <div class="drawer-head">
    <div class="avatar"><?= strtoupper(substr((string)($_SESSION['name'] ?? 'U'), 0, 1)) ?></div>
    <div>
      <div style="font-weight:800; font-size:1.05rem;">&nbsp;<?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></div>
      <div style="opacity:.9; font-size:.9rem;">&nbsp;<?php $st = pdo()->prepare('SELECT email FROM users WHERE id=?'); $st->execute([current_user_id()]); echo htmlspecialchars(($st->fetch()['email'] ?? '')); ?></div>
    </div>
  </div>
  <div class="drawer-body">
    <h3 style="margin:8px 0 8px;">Update profile</h3>
    <form id="profileForm" class="form-grid">
      <div class="input"><label>Name</label><input name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>"></div>
      <div class="input"><label>Email</label><input type="email" name="email" value="<?php $st = pdo()->prepare('SELECT email FROM users WHERE id=?'); $st->execute([current_user_id()]); echo htmlspecialchars(($st->fetch()['email'] ?? '')); ?>"></div>
      <div class="input"><label>New password</label><input type="password" name="new_password" placeholder="Leave blank to keep"></div>
      <div class="input"><label>Current password (required to save)</label><input type="password" name="current_password" required></div>
      <div class="divider"></div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button class="btn" type="submit">Save changes</button>
        <a class="btn danger" href="backend/logout.php">Logout</a>
        <button class="btn secondary" type="button" id="closeDrawer">Close</button>
      </div>
      <p class="muted" id="profileMsg"></p>
    </form>
  </div>
</aside>
<script>
(function(){
  const btn = document.getElementById('btnProfile');
  const drawer = document.getElementById('profileDrawer');
  const backdrop = document.getElementById('profileBackdrop');
  const closeBtn = document.getElementById('closeDrawer');
  if (!btn || !drawer || !backdrop) return;
  const open = (v)=>{ drawer.classList.toggle('open', v); backdrop.classList.toggle('open', v); };
  btn.addEventListener('click', ()=> open(true));
  backdrop.addEventListener('click', ()=> open(false));
  if (closeBtn) closeBtn.addEventListener('click', ()=> open(false));

  const form = document.getElementById('profileForm');
  const msg = document.getElementById('profileMsg');
  if (form) {
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.textContent = '';
      const fd = new FormData(form);
      const res = await fetch('backend/profile.php', { method:'POST', body: fd });
      const json = await res.json().catch(()=>({ok:false,message:'Error'}));
      msg.textContent = json.message || '';
      if (json.ok) { location.reload(); }
    });
  }
})();
</script>
<?php endif; ?>