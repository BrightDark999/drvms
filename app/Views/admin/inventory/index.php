<?= $this->extend('layouts/admin') ?>

<?= $this->section('breadcrumb') ?>
<li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
<li class="breadcrumb-item active">Emergency Supplies</li>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="invStatTotal"><?= (int) ($summary['total'] ?? 0) ?></h3>
                <p>Medical / emergency SKUs</p>
            </div>
            <div class="icon"><i class="fas fa-medkit"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="invStatLow"><?= (int) ($summary['low_stock'] ?? 0) ?></h3>
                <p>Low stock alerts</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><i class="fas fa-database"></i></h3>
                <p>Shared <code>inventory_items</code> table</p>
            </div>
            <div class="icon"><i class="fas fa-link"></i></div>
        </div>
    </div>
</div>

<div class="alert alert-light border small mb-3">
    <strong>Actions you can do here:</strong>
    add medical/emergency supplies, view details, edit item info, adjust stock (restock or use in response), and deactivate items.
    Relief inventory still manages food/hygiene items from the same table.
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Emergency &amp; medical supplies</h3>
        <div class="card-tools d-flex align-items-center flex-wrap">
            <div class="btn-group btn-group-sm mr-2 mb-1">
                <button type="button" class="btn btn-outline-secondary btn-inv-filter active" data-filter="">All</button>
                <button type="button" class="btn btn-outline-danger btn-inv-filter" data-filter="low">Low stock</button>
            </div>
            <button type="button" class="btn btn-sm btn-primary drms-icon-action mb-1" id="btnAddInventory" title="Add supply item">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tblInventory" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Location</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="inventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formInventory" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="crudRecordId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="inventoryModalTitle">Add supply item</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" class="form-control" required maxlength="64" placeholder="MED-BAND-L">
                    </div>
                    <div class="form-group col-md-8">
                        <label>Item name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="255">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-control" required>
                            <?php foreach ($drmsCategories as $catKey): ?>
                                <option value="<?= esc($catKey) ?>"><?= esc($categories[$catKey] ?? $catKey) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Unit</label>
                        <input type="text" name="unit" class="form-control" maxlength="32" value="pc" placeholder="kits, rolls…">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Low-stock alert at</label>
                        <input type="number" name="low_stock_threshold" class="form-control" min="0" step="0.01" placeholder="Optional">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Warehouse / storage</label>
                        <input type="text" name="warehouse_location" class="form-control" maxlength="160" placeholder="MDRRMO supply room">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Expiry date</label>
                        <input type="date" name="expires_at" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Batch number, supplier, handling notes…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="inventoryViewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inventoryViewTitle">Supply details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="inventoryViewBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryAdjustModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="formInventoryAdjust" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" id="adjustItemId" value="">
            <div class="modal-header">
                <h5 class="modal-title">Adjust stock</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong id="adjustItemLabel"></strong></p>
                <p class="text-muted small mb-3">Current quantity: <span id="adjustCurrentQty"></span></p>
                <div class="form-group">
                    <label>Action <span class="text-danger">*</span></label>
                    <select name="direction" class="form-control" required>
                        <option value="add">Restock / receive (+)</option>
                        <option value="remove">Used in response / deploy (−)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity_delta" class="form-control" min="0.01" step="0.01" required>
                </div>
                <div class="form-group mb-0">
                    <label>Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" required maxlength="500" placeholder="e.g. Deployed to flood response, Barangay Poblacion">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Update stock</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    var categoryLabels = <?= json_encode($categories) ?>;
    var stockFilter = '';

    function stockBadge(row) {
        if (row.is_low_stock) {
            return '<span class="badge badge-danger">Low stock</span>';
        }
        return '<span class="badge badge-success">OK</span>';
    }

    function actions(row) {
        return '<div class="btn-group btn-group-xs drms-table-actions">'
            + '<button type="button" class="btn btn-secondary btn-inv-view" data-id="' + row.id + '" title="View details"><i class="fas fa-eye"></i></button>'
            + '<button type="button" class="btn btn-info btn-api-edit" data-id="' + row.id + '" title="Edit"><i class="fas fa-edit"></i></button>'
            + '<button type="button" class="btn btn-warning btn-inv-adjust" data-id="' + row.id + '" title="Adjust stock"><i class="fas fa-exchange-alt"></i></button>'
            + '<button type="button" class="btn btn-danger btn-inv-deactivate" data-id="' + row.id + '" title="Deactivate"><i class="fas fa-ban"></i></button>'
            + '</div>';
    }

    function refreshSummary() {
        DRMS.apiGet('/inventory?scope=drms').then(function (res) {
            var meta = res.meta || {};
            var summary = meta.summary || {};
            $('#invStatTotal').text(summary.total || 0);
            $('#invStatLow').text(summary.low_stock || 0);
        });
    }

    function renderView(d) {
        var rows = [
            ['SKU', d.sku],
            ['Name', d.name],
            ['Category', d.category_label || categoryLabels[d.category] || d.category],
            ['Quantity', Number(d.quantity).toLocaleString() + ' ' + (d.unit || '')],
            ['Low-stock alert', d.low_stock_threshold != null ? Number(d.low_stock_threshold).toLocaleString() : '—'],
            ['Location', d.warehouse_location || '—'],
            ['Expires', d.expires_at || '—'],
            ['Status', d.is_low_stock ? 'Low stock' : 'OK'],
            ['Notes', d.notes || '—'],
            ['Last updated', d.updated_at || '—']
        ];
        var html = '<dl class="row mb-0">';
        rows.forEach(function (r) {
            html += '<dt class="col-sm-4">' + r[0] + '</dt><dd class="col-sm-8">' + $('<div>').text(r[1]).html() + '</dd>';
        });
        html += '</dl>';
        return html;
    }

    var table = DRMS.apiDataTable('#tblInventory', '/inventory?scope=drms', [
        { data: 'sku' },
        { data: 'name' },
        { data: 'category', render: function (c, t, row) { return row.category_label || categoryLabels[c] || c || '—'; } },
        { data: 'quantity', render: function (q) { return q != null ? Number(q).toLocaleString() : '—'; } },
        { data: 'unit', defaultContent: '—' },
        { data: 'warehouse_location', defaultContent: '—' },
        { data: 'expires_at', defaultContent: '—' },
        { data: null, render: function (row) { return stockBadge(row); } },
        { data: null, orderable: false, searchable: false, render: function (row) { return actions(row); } }
    ], {
        order: [[1, 'asc']],
        pageLength: 25,
        mapData: function (res) {
            var rows = res.data || [];
            if (stockFilter === 'low') {
                rows = rows.filter(function (r) { return r.is_low_stock; });
            }
            return rows;
        }
    });

    $('.btn-inv-filter').on('click', function () {
        $('.btn-inv-filter').removeClass('active');
        $(this).addClass('active');
        stockFilter = $(this).data('filter') || '';
        if (table) {
            table.ajax.reload(null, false);
        }
    });

    DRMS.bindApiCrud({
        resource: '/inventory',
        tableSelector: '#tblInventory',
        dataTable: table,
        form: '#formInventory',
        modal: '#inventoryModal',
        addBtn: '#btnAddInventory',
        idField: '#crudRecordId',
        deleteConfirm: 'Deactivate this supply item? It will be hidden from DRMS but kept in the shared table for audit.',
        onReset: function () {
            $('#inventoryModalTitle').text('Add supply item');
            $('input[name=quantity]').prop('readonly', false);
        },
        onLoad: function (d) {
            $('#inventoryModalTitle').text('Edit supply item');
            $('input[name=sku]').val(d.sku || '');
            $('input[name=name]').val(d.name || '');
            $('select[name=category]').val(d.category || 'medical');
            $('input[name=quantity]').val(d.quantity != null ? d.quantity : 0).prop('readonly', true);
            $('input[name=unit]').val(d.unit || 'pc');
            $('input[name=low_stock_threshold]').val(d.low_stock_threshold != null ? d.low_stock_threshold : '');
            $('input[name=warehouse_location]').val(d.warehouse_location || '');
            $('input[name=expires_at]').val(d.expires_at || '');
            $('textarea[name=notes]').val(d.notes || '');
        },
        onSaved: function (res, recordId, finish) {
            finish();
            refreshSummary();
        }
    });

    $('#tblInventory').on('click', '.btn-inv-view', function () {
        var id = $(this).data('id');
        DRMS.apiGet('/inventory/' + id).then(function (res) {
            var d = DRMS.apiUnwrap(res);
            $('#inventoryViewTitle').text(d.name || 'Supply details');
            $('#inventoryViewBody').html(renderView(d));
            $('#inventoryViewModal').modal('show');
        }).catch(function (err) {
            DRMS.toast('Error', err.message, 'error');
        });
    });

    $('#tblInventory').on('click', '.btn-inv-adjust', function () {
        var id = $(this).data('id');
        DRMS.apiGet('/inventory/' + id).then(function (res) {
            var d = DRMS.apiUnwrap(res);
            $('#adjustItemId').val(id);
            $('#adjustItemLabel').text((d.sku || '') + ' — ' + (d.name || ''));
            $('#adjustCurrentQty').text(Number(d.quantity).toLocaleString() + ' ' + (d.unit || ''));
            $('#formInventoryAdjust')[0].reset();
            $('#adjustItemId').val(id);
            $('#inventoryAdjustModal').modal('show');
        }).catch(function (err) {
            DRMS.toast('Error', err.message, 'error');
        });
    });

    $('#formInventoryAdjust').on('submit', function (e) {
        e.preventDefault();
        var id = $('#adjustItemId').val();
        var payload = DRMS.formToObject(this);
        DRMS.apiFormSubmit({
            form: this,
            seqKey: '#formInventoryAdjust',
            request: function () {
                return DRMS.apiPost('/inventory/' + id + '/adjust', payload);
            },
            onSuccess: function (res) {
                $('#inventoryAdjustModal').modal('hide');
                DRMS.toast('Stock updated', res.message || 'Quantity adjusted.', 'success');
                if (table) {
                    table.ajax.reload(null, false);
                }
                refreshSummary();
            }
        });
    });

    $('#tblInventory').on('click', '.btn-inv-deactivate', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        DRMS.confirm('Deactivate item?', 'It will be hidden from active lists but kept for audit.', function () {
            DRMS.apiDelete('/inventory/' + id).then(function (res) {
                DRMS.toast('Deactivated', res.message || 'Item deactivated.', 'success');
                if (table) {
                    table.ajax.reload(null, false);
                }
                refreshSummary();
            }).catch(function (err) {
                DRMS.toast('Error', err.message, 'error');
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
