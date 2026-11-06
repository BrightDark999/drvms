<?php if (function_exists('drms_presentation_active') && drms_presentation_active()) : ?>
<div class="alert alert-warning alert-dismissible demo-mode-banner mb-0 rounded-0 border-0">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <strong><i class="fas fa-flask mr-1"></i> Demo mode</strong>.
    <a href="<?= base_url('demo/logout') ?>" class="alert-link ml-2">Exit demo</a>
    <a href="<?= base_url('demo') ?>" class="alert-link ml-2">Switch role</a>
</div>
<?php endif ?>
