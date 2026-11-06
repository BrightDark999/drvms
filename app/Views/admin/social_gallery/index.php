<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">LGU Social Gallery</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="alert alert-light border small mb-3">
    <strong>Curate LGU Facebook updates for the public homepage.</strong>
    Add posts with an image, caption, and optional link to the original Facebook post
    (<a href="<?= esc($facebookPageUrl) ?>" target="_blank" rel="noopener noreferrer"><?= esc($facebookPageName) ?></a>).
    Active posts appear in a <strong>3-column gallery</strong> on the homepage (up to 27 items).
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gallery posts</h3>
        <div class="card-tools">
            <a href="<?= esc($facebookPageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary mr-2">
                <i class="fab fa-facebook"></i> Open Facebook page
            </a>
            <button type="button" class="btn btn-sm btn-primary" id="btnAddGalleryPost">
                <i class="fas fa-plus"></i> Add post
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped table-hover mb-0" id="tblSocialGallery">
            <thead>
                <tr>
                    <th style="width:72px">Order</th>
                    <th style="width:96px">Image</th>
                    <th>Title / caption</th>
                    <th style="width:120px">Published</th>
                    <th style="width:88px">Active</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($posts === []): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No gallery posts yet. Save images and captions from
                        <a href="<?= esc($facebookPageUrl) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
                        to build the homepage grid.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <tr data-id="<?= (int) $post['id'] ?>">
                    <td><?= (int) ($post['sort_order'] ?? 0) ?></td>
                    <td>
                        <img src="<?= esc(base_url($post['image_path'])) ?>" alt="" class="img-thumbnail" style="max-height:48px;max-width:72px;object-fit:cover;">
                    </td>
                    <td>
                        <?php if (trim((string) ($post['title'] ?? '')) !== ''): ?>
                        <strong><?= esc($post['title']) ?></strong><br>
                        <?php endif ?>
                        <span class="text-muted small"><?= esc(mb_strimwidth((string) ($post['caption'] ?? ''), 0, 80, '…')) ?></span>
                    </td>
                    <td class="small"><?= esc($post['published_at'] ? date('M j, Y', strtotime((string) $post['published_at'])) : '—') ?></td>
                    <td>
                        <?php if (! empty($post['is_active'])): ?>
                        <span class="badge badge-success">Yes</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-xs btn-info btn-edit-gallery" data-id="<?= (int) $post['id'] ?>" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-xs btn-danger btn-delete-gallery" data-id="<?= (int) $post['id'] ?>" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="galleryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formGalleryPost" class="modal-content" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" id="galleryPostId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="galleryModalTitle">Add gallery post</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Title <span class="text-muted">(optional)</span></label>
                        <input type="text" name="title" class="form-control" maxlength="255" placeholder="Short headline">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Sort order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Caption</label>
                    <textarea name="caption" class="form-control" rows="3" maxlength="4000" placeholder="Post text from Facebook…"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Facebook post URL</label>
                        <input type="url" name="external_url" class="form-control" maxlength="500" placeholder="https://www.facebook.com/LGUBarasRizal/posts/…">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Published date</label>
                        <input type="datetime-local" name="published_at" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Image <span class="text-danger" id="galleryImageRequired">*</span></label>
                    <input type="file" name="image" id="galleryImageInput" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Save the image from the Facebook post (right-click → Save image). Max 5 MB.</small>
                    <div id="galleryImagePreview" class="mt-2" style="display:none">
                        <img src="" alt="Current image" class="img-thumbnail" style="max-height:120px">
                    </div>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="galleryIsActive" name="is_active" value="1" checked>
                    <label class="custom-control-label" for="galleryIsActive">Show on homepage (active)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save post</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var baseUrl = <?= json_encode(base_url('admin/social-gallery')) ?>;
    var $form = $('#formGalleryPost');
    var $modal = $('#galleryModal');
    var $id = $('#galleryPostId');
    var $imageInput = $('#galleryImageInput');

    function resetForm() {
        $id.val('');
        $form[0].reset();
        $('#galleryIsActive').prop('checked', true);
        $('#galleryImagePreview').hide().find('img').attr('src', '');
        $imageInput.prop('required', true);
        $('#galleryImageRequired').show();
        $('#galleryModalTitle').text('Add gallery post');
    }

    function fillForm(data) {
        $form.find('[name="title"]').val(data.title || '');
        $form.find('[name="caption"]').val(data.caption || '');
        $form.find('[name="external_url"]').val(data.external_url || '');
        $form.find('[name="sort_order"]').val(data.sort_order != null ? data.sort_order : 0);
        if (data.published_at) {
            var dt = String(data.published_at).replace(' ', 'T').slice(0, 16);
            $form.find('[name="published_at"]').val(dt);
        } else {
            $form.find('[name="published_at"]').val('');
        }
        $('#galleryIsActive').prop('checked', !!Number(data.is_active));
        $imageInput.prop('required', false);
        $('#galleryImageRequired').hide();
        if (data.image_path) {
            $('#galleryImagePreview').show().find('img').attr('src', <?= json_encode(rtrim(base_url(), '/') . '/') ?> + String(data.image_path).replace(/^\//, ''));
        } else {
            $('#galleryImagePreview').hide().find('img').attr('src', '');
        }
    }

    $('#btnAddGalleryPost').on('click', function () {
        resetForm();
        $modal.modal('show');
    });

    $('#tblSocialGallery').on('click', '.btn-edit-gallery', function () {
        var id = $(this).data('id');
        $.get(baseUrl + '/show/' + id, function (res) {
            if (res.status !== 'ok') {
                DRMS.toast('Error', res.message || 'Could not load post', 'error');
                return;
            }
            resetForm();
            $id.val(id);
            fillForm(res.data || {});
            $('#galleryModalTitle').text('Edit gallery post');
            $modal.modal('show');
        }).fail(function () {
            DRMS.toast('Error', 'Could not load post', 'error');
        });
    });

    $('#tblSocialGallery').on('click', '.btn-delete-gallery', function () {
        var id = $(this).data('id');
        DRMS.confirm('Delete post?', 'This will remove the item from the homepage gallery.', function () {
            DRMS.postData(baseUrl + '/delete/' + id, {}, function () {
                DRMS.toast('Removed', 'Gallery post deleted', 'success');
                window.location.reload();
            });
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        var recordId = $id.val();
        var url = recordId ? (baseUrl + '/update/' + recordId) : (baseUrl + '/store');
        DRMS.postFormData(url, this, function (res) {
            $modal.modal('hide');
            DRMS.toast('Saved', res.message || 'Post saved', 'success');
            window.location.reload();
        });
    });
})();
</script>
<?= $this->endSection() ?>
