<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="py-5 mt-4">
    <div class="container">
        <div class="text-center mb-5">
            <!-- <span class="badge badge-drms mb-2">Client presentation</span> -->
            <h1 class="section-title mb-2">DRMS demo accounts</h1>
            <p class="text-muted col-lg-9 mx-auto mb-0">
                Demo Scripts to view pages without connecting to backend
            </p>
        </div>

        <?php if (session('error') !== null) : ?>
            <div class="alert alert-danger"><?= esc(session('error')) ?></div>
        <?php endif ?>
        <?php if (session('message') !== null) : ?>
            <div class="alert alert-success"><?= esc(session('message')) ?></div>
        <?php endif ?>

        <div class="row">
            <?php foreach ($accounts as $key => $acc) : ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column">
                        <h5 class="font-weight-bold mb-2"><?= esc($acc['label']) ?></h5>
                        <p class="text-muted small mb-2">
                            <code><?= esc($acc['username']) ?></code><br>
                            <?= esc($acc['email']) ?>
                        </p>
                        <p class="text-muted small flex-grow-1 mb-3">
                            Opens <strong><?= esc($acc['redirect']) ?></strong> with role-appropriate menu items.
                        </p>
                        <a href="<?= base_url('demo/login/' . esc($key)) ?>" class="btn btn-drms-primary btn-block">
                            Enter as <?= esc($acc['label']) ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- <div class="card bg-light border-0 mt-2">
            <div class="card-body">
                <h6 class="font-weight-bold">Tips for showing the system</h6>
                <ul class="small text-muted mb-3">
                    <li>No need to edit controllers — while demo is on, saves are blocked; tables use seeded DB rows.</li>
                    <li>Switch roles anytime — each click logs out the previous demo user first.</li>
                    <li>Password for database logins (if MySQL is running): <code><?= esc($drms->demoPassword) ?></code> — see <code>TEST_ACCOUNTS.md</code>.</li>
                    <li>End demo: use <strong>Exit demo</strong> in the admin banner or visit <a href="<?= base_url('demo/logout') ?>">demo/logout</a>.</li>
                </ul>
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm mr-2">Public homepage</a>
                <a href="<?= url_to('login') ?>" class="btn btn-outline-secondary btn-sm">Normal login</a>
            </div>
        </div> -->
    </div>
</section>
<?= $this->endSection() ?>
