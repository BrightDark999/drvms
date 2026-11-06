<?php
/** @var list<array<string, mixed>> $carouselSlides */
$carouselSlides = $carouselSlides ?? [];
if ($carouselSlides === []) {
    return;
}
?>
<div id="homeCarousel" class="carousel slide drms-home-carousel shadow-sm" data-ride="carousel" data-interval="6500">
    <?php if (count($carouselSlides) > 1): ?>
    <ol class="carousel-indicators drms-carousel-indicators">
        <?php foreach ($carouselSlides as $i => $slide): ?>
        <li data-target="#homeCarousel" data-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>"></li>
        <?php endforeach ?>
    </ol>
    <?php endif ?>
    <div class="carousel-inner">
        <?php foreach ($carouselSlides as $i => $slide):
            $styleKey = (string) ($slide['background_style'] ?? 'gradient-1');
            if (! in_array($styleKey, ['gradient-1', 'gradient-2', 'gradient-3'], true)) {
                $styleKey = 'gradient-1';
            }
            $styleSuffix = match ($styleKey) {
                'gradient-2' => '2',
                'gradient-3' => '3',
                default      => '1',
            };
            $imagePath = trim((string) ($slide['image_path'] ?? ''));
            $slideClass = 'drms-carousel-slide drms-carousel-slide--' . $styleSuffix;
            if ($imagePath !== '') {
                $slideClass .= ' drms-carousel-slide--image';
            }
            $bgStyle = $imagePath !== '' ? ' style="background-image:url(' . esc(base_url($imagePath), 'attr') . ')"' : '';
            $primaryLabel = trim((string) ($slide['button_primary_label'] ?? ''));
            $primaryUrl   = trim((string) ($slide['button_primary_url'] ?? ''));
            $secondaryLabel = trim((string) ($slide['button_secondary_label'] ?? ''));
            $secondaryUrl   = trim((string) ($slide['button_secondary_url'] ?? ''));
            ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <div class="<?= $slideClass ?>"<?= $bgStyle ?>>
                <div class="container h-100">
                    <div class="row h-100 align-items-center">
                        <div class="col-lg-8 drms-carousel-copy">
                            <?php if (trim((string) ($slide['badge_text'] ?? '')) !== ''): ?>
                            <span class="badge badge-drms mb-3"><?= esc($slide['badge_text']) ?></span>
                            <?php endif ?>
                            <h2 class="display-4 font-weight-bold text-white mb-3"><?= esc($slide['title'] ?? '') ?></h2>
                            <?php if (trim((string) ($slide['description'] ?? '')) !== ''): ?>
                            <p class="lead text-white-75 mb-4"><?= esc($slide['description']) ?></p>
                            <?php endif ?>
                            <?php if ($primaryLabel !== '' && $primaryUrl !== ''): ?>
                            <a href="<?= esc($primaryUrl, 'attr') ?>" class="btn btn-drms-light btn-lg mr-2 mb-2"><?= esc($primaryLabel) ?></a>
                            <?php endif ?>
                            <?php if ($secondaryLabel !== '' && $secondaryUrl !== ''): ?>
                            <a href="<?= esc($secondaryUrl, 'attr') ?>" class="btn btn-outline-light btn-lg mb-2"><?= esc($secondaryLabel) ?></a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    <?php if (count($carouselSlides) > 1): ?>
    <a class="carousel-control-prev" href="#homeCarousel" role="button" data-slide="prev" aria-label="Previous slide">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    </a>
    <a class="carousel-control-next" href="#homeCarousel" role="button" data-slide="next" aria-label="Next slide">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
    </a>
    <?php endif ?>
</div>
