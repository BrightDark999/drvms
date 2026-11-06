<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.useMagicLink') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="drms-login-card">
    <div class="drms-login-card-header">
        <h2><?= lang('Auth.useMagicLink') ?></h2>
        <p>We will email you a sign-in link</p>
    </div>
    <div class="drms-login-card-body">

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

        <form action="<?= url_to('magic-link') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="magicEmail"><?= lang('Auth.email') ?></label>
                <input type="email" class="form-control" id="magicEmail" name="email"
                       value="<?= old('email', auth()->user()->email ?? null) ?>" required>
            </div>

            <button type="submit" class="btn btn-drms-login"><?= lang('Auth.send') ?></button>
        </form>

        <p class="drms-login-footer-links mt-3 mb-0">
            <a href="<?= url_to('login') ?>"><?= lang('Auth.backToLogin') ?></a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>
