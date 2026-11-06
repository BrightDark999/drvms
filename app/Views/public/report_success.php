<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="container py-5 text-center">
    <div class="card shadow-sm border-0 mx-auto" style="max-width:720px">
        <div class="card-body p-5 text-left">
            <div class="text-center text-success mb-3" style="font-size:3rem"><i class="fas fa-check-circle"></i></div>
            <h1 class="h4 text-center">Report received</h1>
            <?php if ($incident): ?>
                <p class="text-center mb-3">Reference: <strong><?= esc($incident['reference_code'] ?? '') ?></strong></p>
                <p class="text-muted small text-center">Priority: <?= esc($incident['priority_tag'] ?? 'MEDIUM') ?> — MDRRMO will verify and coordinate response.</p>
                <?php if (! empty($media)): ?>
                    <hr>
                    <h2 class="h6">Your uploaded evidence</h2>
                    <?= view('partials/incident_media_gallery', ['media' => $media]) ?>
                <?php endif ?>
            <?php else: ?>
                <p class="text-muted text-center">Your report was submitted.</p>
            <?php endif ?>
            <div class="text-center mt-4">
                <?php if (auth()->loggedIn()): ?>
                    <a href="<?= base_url('profile') ?>" class="btn btn-outline-danger mr-2">My profile</a>
                <?php endif ?>
                <a href="<?= base_url('/') ?>" class="btn btn-danger">Back to home</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
