<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Homepage Carousel</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="alert alert-light border small mb-3">
    <strong>Manage homepage hero slides.</strong>
    Add, edit, or remove carousel slides shown on the public homepage. Upload a background image (optional) or use a built-in gradient theme.
    Only slides marked <em>Active</em> appear on the site.
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Carousel slides</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary" id="btnAddSlide">
                <i class="fas fa-plus"></i> Add slide
            </button>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped table-hover mb-0" id="tblCarouselSlides">
            <thead>
                <tr>
                    <th style="width:72px">Order</th>
                    <th style="width:96px">Preview</th>
                    <th>Title</th>
                    <th>Badge</th>
                    <th style="width:88px">Active</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($slides === []): ?>
                <tr id="carouselEmptyRow">
                    <td colspan="6" class="text-center text-muted py-4">No slides yet. Add one to populate the homepage carousel.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($slides as $slide): ?>
                <tr data-id="<?= (int) $slide['id'] ?>">
                    <td><?= (int) ($slide['sort_order'] ?? 0) ?></td>
                    <td>
                        <?php if (! empty($slide['image_path'])): ?>
                        <img src="<?= esc(base_url($slide['image_path'])) ?>" alt="" class="img-thumbnail" style="max-height:48px;max-width:72px;object-fit:cover;">
                        <?php else: ?>
                        <span class="badge badge-secondary"><?= esc($slide['background_style'] ?? 'gradient-1') ?></span>
                        <?php endif ?>
                    </td>
                    <td><?= esc($slide['title'] ?? '') ?></td>
                    <td><?= esc($slide['badge_text'] ?? '') ?></td>
                    <td>
                        <?php if (! empty($slide['is_active'])): ?>
                        <span class="badge badge-success">Yes</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-xs btn-info btn-edit-slide" data-id="<?= (int) $slide['id'] ?>" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-xs btn-danger btn-delete-slide" data-id="<?= (int) $slide['id'] ?>" title="Delete">
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

<div class="modal fade" id="carouselModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formCarouselSlide" class="modal-content" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" id="carouselSlideId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="carouselModalTitle">Add carousel slide</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Sort order</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Badge text</label>
                    <input type="text" name="badge_text" class="form-control" maxlength="120" placeholder="e.g. Citizens &amp; Communities">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="2000"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Background theme</label>
                        <select name="background_style" class="form-control" id="carouselBackgroundStyle">
                            <?php foreach ($backgroundStyles as $key => $label): ?>
                            <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <small class="text-muted">Used when no image is uploaded (or as overlay tint).</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Background image</label>
                        <input type="file" name="image" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                        <div id="carouselImagePreview" class="mt-2" style="display:none">
                            <img src="" alt="Current image" class="img-thumbnail" style="max-height:100px">
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="carouselRemoveImage" name="remove_image" value="1">
                                <label class="custom-control-label" for="carouselRemoveImage">Remove current image</label>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <p class="small text-muted mb-2">Call-to-action buttons (optional)</p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Primary button label</label>
                        <input type="text" name="button_primary_label" class="form-control" maxlength="80">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Primary button URL</label>
                        <input type="text" name="button_primary_url" class="form-control" maxlength="500" placeholder="/report or full URL">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Secondary button label</label>
                        <input type="text" name="button_secondary_label" class="form-control" maxlength="80">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Secondary button URL</label>
                        <input type="text" name="button_secondary_url" class="form-control" maxlength="500">
                    </div>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="carouselIsActive" name="is_active" value="1" checked>
                    <label class="custom-control-label" for="carouselIsActive">Show on homepage (active)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save slide</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var baseUrl = <?= json_encode(base_url('admin/home-carousel')) ?>;
    var $form = $('#formCarouselSlide');
    var $modal = $('#carouselModal');
    var $id = $('#carouselSlideId');

    function resetForm() {
        $id.val('');
        $form[0].reset();
        $('#carouselIsActive').prop('checked', true);
        $('#carouselRemoveImage').prop('checked', false);
        $('#carouselImagePreview').hide().find('img').attr('src', '');
        $('#carouselModalTitle').text('Add carousel slide');
    }

    function fillForm(data) {
        $form.find('[name="title"]').val(data.title || '');
        $form.find('[name="badge_text"]').val(data.badge_text || '');
        $form.find('[name="description"]').val(data.description || '');
        $form.find('[name="background_style"]').val(data.background_style || 'gradient-1');
        $form.find('[name="sort_order"]').val(data.sort_order != null ? data.sort_order : 0);
        $form.find('[name="button_primary_label"]').val(data.button_primary_label || '');
        $form.find('[name="button_primary_url"]').val(data.button_primary_url || '');
        $form.find('[name="button_secondary_label"]').val(data.button_secondary_label || '');
        $form.find('[name="button_secondary_url"]').val(data.button_secondary_url || '');
        $('#carouselIsActive').prop('checked', !!Number(data.is_active));
        $('#carouselRemoveImage').prop('checked', false);
        if (data.image_path) {
            $('#carouselImagePreview').show().find('img').attr('src', <?= json_encode(rtrim(base_url(), '/') . '/') ?> + String(data.image_path).replace(/^\//, ''));
        } else {
            $('#carouselImagePreview').hide().find('img').attr('src', '');
        }
    }

    $('#btnAddSlide').on('click', function () {
        resetForm();
        $modal.modal('show');
    });

    $('#tblCarouselSlides').on('click', '.btn-edit-slide', function () {
        var id = $(this).data('id');
        $.get(baseUrl + '/show/' + id, function (res) {
            if (res.status !== 'ok') {
                DRMS.toast('Error', res.message || 'Could not load slide', 'error');
                return;
            }
            resetForm();
            $id.val(id);
            fillForm(res.data || {});
            $('#carouselModalTitle').text('Edit carousel slide');
            $modal.modal('show');
        }).fail(function () {
            DRMS.toast('Error', 'Could not load slide', 'error');
        });
    });

    $('#tblCarouselSlides').on('click', '.btn-delete-slide', function () {
        var id = $(this).data('id');
        DRMS.confirm('Delete slide?', 'This slide will be removed from the homepage carousel.', function () {
            DRMS.postData(baseUrl + '/delete/' + id, {}, function (res) {
                DRMS.toast('Removed', res.message || 'Slide deleted', 'success');
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
            DRMS.toast('Saved', res.message || 'Slide saved', 'success');
            window.location.reload();
        });
    });
})();
</script>
<?= $this->endSection() ?>
