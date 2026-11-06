<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?>Pending Approval<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="drms-login-card">
    <div class="drms-login-card-header">
        <h2>Account pending approval</h2>
        <p>Your registration was received successfully.</p>
    </div>
    <div class="drms-login-card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= esc(session('message')) ?></div>
        <?php endif ?>
        <p class="mb-3">
            Your account was registered as a <strong>resident</strong>. An MDRRMO or system administrator must verify your ID and approve your account before you can sign in.
        </p>
        <ul class="small text-muted pl-3 mb-4">
            <li>You will receive an email when your account is approved (if SMTP is configured).</li>
            <li>After approval, staff may assign you a role such as Volunteer or MDRRMO / LGU Staff if applicable.</li>
            <li>Contact your local MDRRMO if approval takes longer than expected.</li>
        </ul>
        <a href="<?= url_to('login') ?>" class="btn btn-drms-login btn-block">Back to login</a>
    </div>
</div>
<?= $this->endSection() ?>
