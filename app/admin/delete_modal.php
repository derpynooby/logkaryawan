<?php
function admin_delete_modal_styles(): void { ?>
<style>
.del-modal-overlay{
  position:fixed;inset:0;z-index:500;
  background:rgba(28,19,9,.5);
  display:flex;align-items:center;justify-content:center;
  padding:1rem;
  opacity:0;visibility:hidden;
  transition:opacity .22s,visibility .22s;
}
.del-modal-overlay.is-open{opacity:1;visibility:visible}
.del-modal{
  background:#fff;border-radius:.85rem;
  border:1px solid var(--border);
  width:100%;max-width:400px;
  padding:1.5rem;
  box-shadow:0 20px 50px rgba(28,19,9,.18);
  transform:scale(.94) translateY(8px);
  transition:transform .22s;
}
.del-modal-overlay.is-open .del-modal{transform:scale(1) translateY(0)}
.del-modal-icon{
  width:48px;height:48px;border-radius:50%;
  background:#fef2f2;border:1px solid #fecaca;
  color:var(--danger);font-size:1.4rem;font-weight:800;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 1rem;
}
.del-modal h3{text-align:center;font-size:1.05rem;font-weight:800;margin-bottom:.5rem;color:var(--text)}
.del-modal p{text-align:center;font-size:.875rem;color:var(--muted);line-height:1.6;margin-bottom:1.25rem}
.del-modal p strong{color:var(--text)}
.del-modal-actions{display:flex;gap:.5rem}
.del-modal-actions .btn{flex:1;text-align:center}
</style>
<?php }

function admin_delete_modal_html(): void { ?>
<div class="del-modal-overlay" id="delModal" aria-hidden="true">
  <div class="del-modal" role="dialog" aria-labelledby="delModalTitle" aria-modal="true">
    <div class="del-modal-icon">!</div>
    <h3 id="delModalTitle">Hapus Karyawan?</h3>
    <p>Anda akan menghapus karyawan <strong id="delModalName"></strong>. Tindakan ini tidak dapat dibatalkan.</p>
    <div class="del-modal-actions">
      <button type="button" class="btn btn-ghost" id="delModalCancel">Batal</button>
      <button type="button" class="btn btn-danger" id="delModalConfirm">Ya, Hapus</button>
    </div>
  </div>
</div>
<form id="delModalForm" method="post" action="action.php" hidden>
  <input type="hidden" name="action" value="delete_karyawan">
  <?= csrf_field() ?>
  <input type="hidden" name="id" id="delModalId" value="">
</form>
<?php }

function admin_delete_modal_script(): void { ?>
<script>
(function(){
  var overlay=document.getElementById('delModal');
  var form=document.getElementById('delModalForm');
  var idInput=document.getElementById('delModalId');
  var nameEl=document.getElementById('delModalName');
  var btnCancel=document.getElementById('delModalCancel');
  var btnConfirm=document.getElementById('delModalConfirm');
  if(!overlay||!form)return;

  function open(id,name){
    idInput.value=id;
    nameEl.textContent=name;
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden','false');
    document.body.style.overflow='hidden';
    btnConfirm.focus();
  }
  function close(){
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden','true');
    document.body.style.overflow='';
    idInput.value='';
    nameEl.textContent='';
  }

  document.querySelectorAll('[data-delete-karyawan]').forEach(function(btn){
    btn.addEventListener('click',function(){
      open(btn.getAttribute('data-id'),btn.getAttribute('data-name'));
    });
  });
  btnCancel.addEventListener('click',close);
  overlay.addEventListener('click',function(e){if(e.target===overlay)close()});
  btnConfirm.addEventListener('click',function(){form.submit()});
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&overlay.classList.contains('is-open'))close();
  });
})();
</script>
<?php }
