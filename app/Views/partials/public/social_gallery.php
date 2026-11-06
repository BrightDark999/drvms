<?php
/** @var string $facebookPageUrl */
/** @var string $facebookPageName */
/** @var bool $facebookPageEmbedEnabled */
$facebookPageUrl    = $facebookPageUrl ?? drms_config()->facebookPageUrl;
$facebookPageName   = $facebookPageName ?? drms_config()->facebookPageName;
$facebookPageEmbedEnabled = $facebookPageEmbedEnabled ?? drms_config()->facebookPageEmbedEnabled;

if (! $facebookPageEmbedEnabled) {
    return;
}
?>
<section id="lgu-updates" class="py-5 drms-section-alt drms-fb-screen">
    <div class="container">
        <div class="text-center mb-4 mb-lg-5">
            <span class="badge badge-drms mb-2">Facebook Official Page</span>
            <h2 class="section-title mb-2"><?= esc($facebookPageName) ?></h2>
            <p class="text-muted col-lg-8 mx-auto mb-0">
                Official municipal announcements and advisories from <?= esc($facebookPageName) ?>.
                Scroll inside the panel below to read the latest posts.
            </p>
        </div>

        <div class="drms-fb-panel-wrapper">
            <div class="drms-fb-panel">
                <div class="drms-fb-panel-header">
                    <div class="drms-fb-panel-brand">
                        <span class="drms-fb-panel-icon" aria-hidden="true"><i class="fab fa-facebook-f"></i></span>
                        <div class="drms-fb-panel-brand-text">
                            <span class="drms-fb-panel-title">Facebook Official Page</span>
                            <span class="drms-fb-panel-sub"><?= esc($facebookPageName) ?></span>
                        </div>
                    </div>
                    <a href="<?= esc($facebookPageUrl, 'attr') ?>" class="btn btn-sm btn-outline-primary drms-fb-panel-link" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-external-link-alt mr-1"></i> Open on Facebook
                    </a>
                </div>
                <div id="drms-fb-embed-wrap"
                     class="drms-fb-panel-viewport"
                     data-page-url="<?= esc($facebookPageUrl, 'attr') ?>">
                    <div class="drms-fb-panel-viewport-inner">
                        <div id="drms-fb-embed-scaler" class="drms-fb-screen-scaler">
                            <iframe
                                id="drms-fb-embed-frame"
                                title="<?= esc($facebookPageName) ?> — Facebook Official Page"
                                src="about:blank"
                                scrolling="yes"
                                frameborder="0"
                                allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
