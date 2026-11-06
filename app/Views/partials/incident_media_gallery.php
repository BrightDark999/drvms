<?php
/**
 * @var list<array<string, mixed>> $media
 */
$media = $media ?? [];
?>
<?php if ($media === []): ?>
    <p class="text-muted small mb-0">No photos or videos attached.</p>
<?php else: ?>
    <div class="row drms-incident-media-gallery">
        <?php foreach ($media as $item): ?>
            <?php
            $url  = (string) ($item['url'] ?? '');
            $name = (string) ($item['original_name'] ?? basename($item['path'] ?? 'file'));
            if ($url === '') {
                continue;
            }
            ?>
            <div class="col-sm-6 col-md-4 mb-3">
                <div class="card h-100 border">
                    <div class="card-body p-2 text-center">
                        <?php if (! empty($item['is_image'])): ?>
                            <a href="<?= esc($url) ?>" target="_blank" rel="noopener">
                                <img src="<?= esc($url) ?>" alt="<?= esc($name) ?>" class="img-fluid rounded drms-incident-media-thumb">
                            </a>
                        <?php elseif (! empty($item['is_video'])): ?>
                            <video src="<?= esc($url) ?>" class="w-100 rounded drms-incident-media-video" controls preload="metadata"></video>
                        <?php else: ?>
                            <a href="<?= esc($url) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-paperclip"></i> <?= esc($name) ?>
                            </a>
                        <?php endif ?>
                        <div class="small text-muted mt-2 text-truncate" title="<?= esc($name) ?>"><?= esc($name) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>
