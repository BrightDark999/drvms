<!-- Honeypot anti-bot protection -->
<div style="display:none !important; visibility:hidden !important; position:absolute; left:-9999px;" aria-hidden="true">
    <label for="drms_hp_website">Website field (leave blank)</label>
    <input type="text" name="drms_website_hp" id="drms_hp_website" value="" tabindex="-1" autocomplete="off">
</div>

<!-- Visual CAPTCHA protection -->
<div class="form-group mb-3">
    <label for="drmsCaptchaInput" class="font-weight-bold d-block">Security Verification <span class="text-danger">*</span></label>
    <div class="d-flex align-items-center mb-2">
        <img src="<?= base_url('captcha/image') ?>?_t=<?= time() ?>" id="drmsCaptchaImg" alt="Security CAPTCHA" class="border rounded" style="height:44px; width:160px; object-fit:contain; background:#2b1b1d;">
        <button type="button" class="btn btn-sm btn-outline-secondary ml-2" onclick="document.getElementById('drmsCaptchaImg').src='<?= base_url('captcha/image') ?>?_t='+Date.now()" title="Refresh security code">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <input type="text" name="captcha_code" id="drmsCaptchaInput" class="form-control" placeholder="Enter the 5 characters above" maxlength="6" required autocomplete="off" style="max-width:240px; text-transform:uppercase; letter-spacing:2px;">
    <small class="text-muted">Type the letters and numbers shown above to verify you are human.</small>
</div>
