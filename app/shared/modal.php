<?php
/**
 * shared/modal.php
 * Custom popup modal system — menggantikan confirm() dan alert() browser native.
 *
 * Cara pakai:
 *   1. Di setiap halaman yang butuh modal, panggil modal_styles() setelah html_head()
 *   2. Sebelum html_foot(), panggil modal_html() lalu modal_script()
 *   3. Untuk konfirmasi hapus:
 *        <button data-confirm-delete data-id="5" data-name="Nama Item"
 *                data-form="formDeleteId" data-label="logbook">Hapus</button>
 *        <form id="formDeleteId" ...> ... </form>
 *   4. Untuk konfirmasi custom:
 *        <button data-confirm data-title="Judul?" data-msg="Pesan"
 *                data-form="formId">Aksi</button>
 */

function modal_styles(): void { ?>
<style>
/* ── Modal overlay ── */
.lk-overlay{
  position:fixed;inset:0;z-index:600;
  background:rgba(28,19,9,.48);
  display:flex;align-items:center;justify-content:center;padding:1rem;
  opacity:0;visibility:hidden;
  transition:opacity .2s,visibility .2s;
}
.lk-overlay.is-open{opacity:1;visibility:visible}

/* ── Modal box ── */
.lk-modal{
  background:#fff;border-radius:1rem;
  border:1px solid var(--border);
  width:100%;max-width:420px;
  padding:1.75rem 1.5rem 1.5rem;
  box-shadow:0 24px 60px rgba(28,19,9,.18);
  transform:scale(.93) translateY(10px);
  transition:transform .2s;
  position:relative;
}
.lk-overlay.is-open .lk-modal{transform:scale(1) translateY(0)}

/* ── Icon ── */
.lk-modal-icon{
  width:52px;height:52px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;margin:0 auto 1rem;
  flex-shrink:0;
}
.lk-modal-icon.danger{background:#fef2f2;border:1.5px solid #fecaca;color:#b94040}
.lk-modal-icon.warn  {background:#fffbeb;border:1.5px solid #fde68a;color:#b87a1a}
.lk-modal-icon.info  {background:#eff6ff;border:1.5px solid #bfdbfe;color:#1d4ed8}
.lk-modal-icon.success{background:#ecfdf5;border:1.5px solid #a7f3d0;color:#3a7d52}

/* ── Content ── */
.lk-modal h3{
  text-align:center;font-size:1.05rem;font-weight:800;
  margin-bottom:.4rem;color:var(--text)
}
.lk-modal p{
  text-align:center;font-size:.875rem;color:var(--muted);
  line-height:1.65;margin-bottom:1.4rem
}
.lk-modal p strong{color:var(--text)}

/* ── Actions ── */
.lk-modal-actions{display:flex;gap:.6rem}
.lk-modal-actions .btn{flex:1;text-align:center;justify-content:center}

/* ── Close X ── */
.lk-modal-close{
  position:absolute;top:.75rem;right:.75rem;
  background:none;border:none;cursor:pointer;
  color:var(--muted);font-size:1.1rem;line-height:1;
  padding:.25rem .35rem;border-radius:.35rem;
  transition:background .15s,color .15s;
}
.lk-modal-close:hover{background:#f5ece0;color:var(--text)}
</style>
<?php }

function modal_html(): void { ?>
<!-- Custom Modal — shared/modal.php -->
<div class="lk-overlay" id="lkModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="lkModalTitle">
  <div class="lk-modal">
    <button class="lk-modal-close" id="lkModalClose" aria-label="Tutup">✕</button>
    <div class="lk-modal-icon" id="lkModalIcon">!</div>
    <h3 id="lkModalTitle"></h3>
    <p id="lkModalMsg"></p>
    <div class="lk-modal-actions" id="lkModalActions">
      <button type="button" class="btn btn-ghost" id="lkModalCancel">Batal</button>
      <button type="button" class="btn btn-danger" id="lkModalConfirm">Konfirmasi</button>
    </div>
  </div>
</div>
<?php }

function modal_script(): void { ?>
<script>
(function(){
  var overlay  = document.getElementById('lkModal');
  var iconEl   = document.getElementById('lkModalIcon');
  var titleEl  = document.getElementById('lkModalTitle');
  var msgEl    = document.getElementById('lkModalMsg');
  var cancelEl = document.getElementById('lkModalCancel');
  var confirmEl= document.getElementById('lkModalConfirm');
  var closeEl  = document.getElementById('lkModalClose');
  if(!overlay) return;

  var _pendingForm = null;
  var _pendingFn   = null;

  var ICONS = {
    danger : '🗑️',
    warn   : '⚠️',
    info   : 'ℹ️',
    success: '✓'
  };

  function openModal(opts){
    var type = opts.type || 'danger';
    iconEl.className  = 'lk-modal-icon ' + type;
    iconEl.textContent = ICONS[type] || '!';
    titleEl.textContent = opts.title || 'Konfirmasi';
    msgEl.innerHTML     = opts.msg   || '';

    // confirm button label & style
    confirmEl.textContent = opts.confirmLabel || 'Konfirmasi';
    confirmEl.className   = 'btn ' + (opts.confirmClass || 'btn-danger');

    // cancel button
    cancelEl.textContent = opts.cancelLabel || 'Batal';
    cancelEl.style.display = opts.hideCanccel ? 'none' : '';

    _pendingForm = opts.form || null;
    _pendingFn   = opts.onConfirm || null;

    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
    confirmEl.focus();
  }

  function closeModal(){
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
    _pendingForm = null;
    _pendingFn   = null;
  }

  // Confirm action
  confirmEl.addEventListener('click', function(){
    if(_pendingForm) _pendingForm.submit();
    if(_pendingFn)   _pendingFn();
    closeModal();
  });

  // Close triggers
  cancelEl.addEventListener('click', closeModal);
  closeEl.addEventListener('click', closeModal);
  overlay.addEventListener('click', function(e){ if(e.target===overlay) closeModal(); });
  document.addEventListener('keydown', function(e){
    if(e.key==='Escape' && overlay.classList.contains('is-open')) closeModal();
  });

  // ── Auto-wire: data-confirm-delete buttons ───────────────────────────
  // <button data-confirm-delete data-id="5" data-name="Budi" data-form="formId" data-label="karyawan">
  document.querySelectorAll('[data-confirm-delete]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var formId = btn.getAttribute('data-form');
      var form   = formId ? document.getElementById(formId) : null;
      // inject id into form if present
      var idInput = form ? form.querySelector('[name="id"]') : null;
      if(idInput) idInput.value = btn.getAttribute('data-id') || '';

      openModal({
        type        : 'danger',
        title       : 'Hapus ' + (btn.getAttribute('data-label') || 'data') + '?',
        msg         : 'Anda akan menghapus <strong>' + escHtml(btn.getAttribute('data-name') || '') + '</strong>.<br>Tindakan ini tidak dapat dibatalkan.',
        confirmLabel: 'Ya, Hapus',
        confirmClass: 'btn-danger',
        form        : form
      });
    });
  });

  // ── Auto-wire: data-confirm buttons (generic) ─────────────────────────
  // <button data-confirm data-title="Judul" data-msg="Pesan" data-confirm-label="OK" data-form="formId">
  document.querySelectorAll('[data-confirm]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var formId = btn.getAttribute('data-form');
      var form   = formId ? document.getElementById(formId) : null;
      openModal({
        type        : btn.getAttribute('data-type') || 'warn',
        title       : btn.getAttribute('data-title') || 'Konfirmasi',
        msg         : btn.getAttribute('data-msg') || 'Lanjutkan?',
        confirmLabel: btn.getAttribute('data-confirm-label') || 'Ya, Lanjutkan',
        confirmClass: btn.getAttribute('data-confirm-class') || 'btn-primary',
        form        : form
      });
    });
  });

  // ── Expose global API ─────────────────────────────────────────────────
  window.LKModal = { open: openModal, close: closeModal };

  function escHtml(s){
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>
<?php }
