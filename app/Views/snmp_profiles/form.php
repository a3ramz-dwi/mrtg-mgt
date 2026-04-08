<?php declare(strict_types=1);
$profile = $profile ?? null;
$version = (string)($profile['version'] ?? '2c');
?>
<h3 class="mb-3"><?= $mode === 'edit' ? 'Edit SNMP Profile' : 'Add SNMP Profile' ?></h3>

<form method="post" action="<?= $mode === 'edit' ? '/snmp-profiles/edit?id='.(int)$profile['id'] : '/snmp-profiles/create' ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Profile Name</label>
      <input class="form-control" name="name" required value="<?= htmlspecialchars((string)($profile['name'] ?? '')) ?>">
      <div class="form-text">Example: default-public, core-snmp, dc1-community</div>
    </div>

    <div class="col-md-3">
      <label class="form-label">SNMP Version</label>
      <select class="form-select" name="version" id="snmpVersion">
        <option value="1" <?= $version==='1'?'selected':'' ?>>v1</option>
        <option value="2c" <?= $version==='2c'?'selected':'' ?>>v2c</option>
        <option value="3" <?= $version==='3'?'selected':'' ?>>v3 (disabled for MVP)</option>
      </select>
      <div class="form-text">MVP: gunakan v2c.</div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Community (v1/v2c)</label>
      <input class="form-control" name="community" value="<?= htmlspecialchars((string)($profile['community'] ?? 'public')) ?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Timeout (ms)</label>
      <input class="form-control" type="number" min="200" max="30000" name="timeout_ms" value="<?= (int)($profile['timeout_ms'] ?? 1500) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Retries</label>
      <input class="form-control" type="number" min="0" max="10" name="retries" value="<?= (int)($profile['retries'] ?? 1) ?>">
    </div>

    <div class="col-12">
      <div class="accordion" id="v3Accordion">
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#v3Fields">
              SNMPv3 Fields (prepared for next phase)
            </button>
          </h2>
          <div id="v3Fields" class="accordion-collapse collapse" data-bs-parent="#v3Accordion">
            <div class="accordion-body">
              <div class="alert alert-warning mb-3">
                Tahap ini fokus v2c. Field v3 disimpan untuk fase berikutnya (belum dipakai SNMP engine).
              </div>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Security Level</label>
                  <select class="form-select" name="v3_sec_level">
                    <option value="">--</option>
                    <?php foreach (['noAuthNoPriv','authNoPriv','authPriv'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= (string)($profile['v3_sec_level'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Auth User</label>
                  <input class="form-control" name="v3_auth_user" value="<?= htmlspecialchars((string)($profile['v3_auth_user'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Auth Protocol</label>
                  <select class="form-select" name="v3_auth_proto">
                    <option value="">--</option>
                    <?php foreach (['MD5','SHA','SHA256','SHA512'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= (string)($profile['v3_auth_proto'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Auth Password</label>
                  <input class="form-control" name="v3_auth_pass" value="<?= htmlspecialchars((string)($profile['v3_auth_pass'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Priv Protocol</label>
                  <select class="form-select" name="v3_priv_proto">
                    <option value="">--</option>
                    <?php foreach (['DES','AES','AES128','AES192','AES256'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= (string)($profile['v3_priv_proto'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Priv Password</label>
                  <input class="form-control" name="v3_priv_pass" value="<?= htmlspecialchars((string)($profile['v3_priv_pass'] ?? '')) ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>      
    </div>

  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $mode === 'edit' ? 'Update' : 'Save' ?></button>
    <a class="btn btn-outline-secondary" href="/snmp-profiles">Back</a>
  </div>
</form>

<script>
  (function(){
    var sel = document.getElementById('snmpVersion');
    if(!sel) return;
    sel.addEventListener('change', function(){
      if (sel.value === '3') {
        alert('MVP saat ini fokus SNMP v2c. Silakan pilih v2c.');
        sel.value = '2c';
      }
    });
  })();
</script>
