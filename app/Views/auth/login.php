<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="drms-login-card">
    <div class="drms-login-card-header">
        <h2><?= lang('Auth.login') ?></h2>
        <p>Staff access to the operations dashboard</p>
    </div>
    <div class="drms-login-card-body">

        <?php if (drms_config()->demoModeEnabled) : ?>
            <!-- <div class="drms-login-hint mb-3">
                <strong>Client demo:</strong>
                <a href="<?//= base_url('demo') ?>">demo accounts</a>
            </div> -->
        <?php elseif (ENVIRONMENT === 'development') : ?>
            <div class="drms-login-hint">
                <strong>Local dev:</strong> e.g. <code>mdrrmo</code> / <code>Drms@2026</code>
                — see <code>TEST_ACCOUNTS.md</code>
            </div>
        <?php endif ?>

        <?php if (session('error') !== null) : ?>
            <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
        <?php elseif (session('errors') !== null) : ?>
            <div class="alert alert-danger" role="alert">
                <?php if (is_array(session('errors'))) : ?>
                    <?php foreach (session('errors') as $error) : ?>
                        <?= esc($error) ?><br>
                    <?php endforeach ?>
                <?php else : ?>
                    <?= esc(session('errors')) ?>
                <?php endif ?>
            </div>
        <?php endif ?>

        <?php if (session('message') !== null) : ?>
            <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
        <?php endif ?>

        <form action="<?= url_to('login') ?>" method="post" autocomplete="on">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="loginEmail"><?= lang('Auth.email') ?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    </div>
                    <input type="email"
                           class="form-control"
                           id="loginEmail"
                           name="email"
                           inputmode="email"
                           autocomplete="email"
                           placeholder="you@example.com"
                           value="<?= old('email') ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="loginPassword"><?= lang('Auth.password') ?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    </div>
                    <input type="password"
                           class="form-control"
                           id="loginPassword"
                           name="password"
                           autocomplete="current-password"
                           placeholder="••••••••"
                           required>
                </div>
            </div>

            <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                <div class="form-group form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="rememberMe"
                        <?php if (old('remember')) : ?> checked<?php endif ?>>
                    <label class="form-check-label" for="rememberMe"><?= lang('Auth.rememberMe') ?></label>
                </div>
            <?php endif ?>

            <button type="submit" class="btn btn-drms-login" id="btnLoginSubmit">
                <i class="fas fa-sign-in-alt mr-1"></i> <?= lang('Auth.login') ?>
            </button>

            <!-- </?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                <p class="drms-login-footer-links mt-3 mb-0">
                    </?= lang('Auth.forgotPassword') ?>
                    <a href="</?= url_to('magic-link') ?>"></?= lang('Auth.useMagicLink') ?></a>
                </p>
            </?php endif ?> -->

            <?php if (setting('Auth.allowRegistration')) : ?>
                <p class="drms-login-footer-links mb-0">
                    <?= lang('Auth.needAccount') ?>
                    <a href="<?= url_to('register') ?>"><?= lang('Auth.register') ?></a>
                </p>
            <?php endif ?>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form');
    var overlay = document.getElementById('loginLoaderOverlay');
    if (form && overlay) {
        form.addEventListener('submit', function () {
            if (form.checkValidity()) {
                overlay.style.display = 'flex';
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
