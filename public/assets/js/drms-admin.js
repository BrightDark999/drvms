/**
 * DRMS Admin – CSRF-aware AJAX, SweetAlert2, server-side DataTables
 */
var DRMS = window.DRMS || {};
DRMS.csrf = function () {
    var meta = document.querySelector('meta[name="csrf_test_name"]');
    if (!meta) {
        var input = document.querySelector('input[name="csrf_test_name"]');
        if (input) {
            return { name: input.getAttribute('name'), hash: input.value };
        }
        return { name: 'csrf_test_name', hash: '' };
    }
    return { name: meta.getAttribute('name'), hash: meta.getAttribute('content') };
};
DRMS.applyCsrfToken = function (hash) {
    if (!hash) {
        return;
    }
    var csrf = DRMS.csrf();
    var meta = document.querySelector('meta[name="' + csrf.name + '"]');
    if (meta) {
        meta.setAttribute('content', hash);
    }
    $('input[name="' + csrf.name + '"]').val(hash);
};
$(function () {
    var csrf = DRMS.csrf();
    if (csrf.hash) {
        $.ajaxSetup({
            beforeSend: function (xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type)) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    var live = DRMS.csrf();
                    if (live.hash) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', live.hash);
                    }
                }
            }
        });
    }
    $(document).ajaxComplete(function (event, xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            DRMS.applyCsrfToken(token);
        }
    });
});
DRMS.toast = function (title, text, icon) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon || 'success',
            title: title,
            text: text || '',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
};
DRMS.confirm = function (title, text, onConfirm) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a4d7c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes'
    }).then(function (result) {
        if (result.isConfirmed && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
};
DRMS.showErrors = function (errors) {
    var msg = typeof errors === 'object'
        ? Object.values(errors).join('\n')
        : String(errors);
    Swal.fire({ icon: 'error', title: 'Validation', text: msg });
};
/**
 * POST JSON with CSRF (empty body still sends token via header).
 */
DRMS.postJson = function (url, data, onSuccess) {
    var csrf = DRMS.csrf();
    var payload = data || {};
    if (csrf.hash) {
        payload[csrf.name] = csrf.hash;
    }
    $.ajax({
        url: url,
        method: 'POST',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        dataType: 'json'
    }).done(function (res) {
        if (res.status === 'ok' && typeof onSuccess === 'function') {
            onSuccess(res);
        } else if (res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', res.message || 'Request failed', 'error');
        }
    }).fail(function (xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            DRMS.applyCsrfToken(token);
        }
        var res = xhr.responseJSON;
        if (res && res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', (res && res.message) || 'Server error', 'error');
        }
    });
};
/**
 * POST multipart/form or urlencoded form (includes CSRF hidden field).
 */
DRMS.postData = function (url, data, onSuccess) {
    var csrf = DRMS.csrf();
    var payload = data || {};
    if (csrf.hash) {
        payload[csrf.name] = csrf.hash;
    }
    $.ajax({
        url: url,
        method: 'POST',
        data: payload,
        dataType: 'json'
    }).done(function (res) {
        if (res.status === 'ok' && typeof onSuccess === 'function') {
            onSuccess(res);
        } else if (res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', res.message || 'Request failed', 'error');
        }
    }).fail(function (xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            DRMS.applyCsrfToken(token);
        }
        var res = xhr.responseJSON;
        if (res && res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', (res && res.message) || 'Server error', 'error');
        }
    });
};
DRMS.postForm = function (url, formEl, onSuccess) {
    var $form = $(formEl);
    var csrf = DRMS.csrf();
    if (csrf.hash) {
        var $field = $form.find('input[name="' + csrf.name + '"]');
        if ($field.length) {
            $field.val(csrf.hash);
        }
    }
    $.ajax({
        url: url,
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (res) {
        if (res.status === 'ok' && typeof onSuccess === 'function') {
            onSuccess(res);
        } else if (res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', res.message || 'Request failed', 'error');
        }
    }).fail(function (xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            DRMS.applyCsrfToken(token);
        }
        var res = xhr.responseJSON;
        if (res && res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', (res && res.message) || 'Server error', 'error');
        }
    });
};
DRMS.postFormData = function (url, formEl, onSuccess) {
    var form = formEl instanceof jQuery ? formEl[0] : formEl;
    var fd = new FormData(form);
    var csrf = DRMS.csrf();
    if (csrf.hash) {
        fd.set(csrf.name, csrf.hash);
    }
    $.ajax({
        url: url,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function (res) {
        if (res.status === 'ok' && typeof onSuccess === 'function') {
            onSuccess(res);
        } else if (res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', res.message || 'Request failed', 'error');
        }
    }).fail(function (xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            DRMS.applyCsrfToken(token);
        }
        var res = xhr.responseJSON;
        if (res && res.errors) {
            DRMS.showErrors(res.errors);
        } else {
            DRMS.toast('Error', (res && res.message) || 'Server error', 'error');
        }
    });
};
/**
 * Server-side DataTables — returns DataTable instance for reload().
 */
DRMS.serverDataTable = function (selector, url, columns, options) {
    var opts = options || {};
    if (typeof $.fn.DataTable === 'undefined') {
        return null;
    }
    return $(selector).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            type: 'GET',
            error: function (xhr) {
                var msg = 'Could not load table data.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                DRMS.toast('Data error', msg, 'error');
            }
        },
        columns: columns,
        responsive: true,
        pageLength: 25,
        autoWidth: false,
        order: opts.order || [[0, 'desc']]
    });
};
/**
 * Wire Add / Edit / Delete for admin CRUD modals (demo toast and seeders stay separate).
 *
 * opts: { tableSelector, dataTable, form, modal, addBtn, baseUrl, idField, onReset, onLoad, deleteConfirm }
 */
DRMS.bindCrud = function (opts) {
    var $tbl = $(opts.tableSelector);
    var dt   = opts.dataTable || null;
    var $id  = $(opts.idField || '#crudRecordId');
    if (opts.addBtn) {
        $(opts.addBtn).on('click', function () {
            $id.val('');
            if (opts.form) {
                $(opts.form)[0].reset();
                var csrf = DRMS.csrf();
                if (csrf.hash) {
                    $(opts.form).find('input[name="' + csrf.name + '"]').val(csrf.hash);
                }
            }
            if (typeof opts.onReset === 'function') {
                opts.onReset();
            }
            $(opts.modal).modal('show');
        });
    }
    $tbl.on('click', '.btn-drms-edit', function () {
        var id = $(this).data('id');
        $.get(opts.baseUrl + '/show/' + id, function (res) {
            if (res.status !== 'ok') {
                DRMS.toast('Error', res.message || 'Could not load record', 'error');
                return;
            }
            $id.val(id);
            if (typeof opts.onLoad === 'function') {
                opts.onLoad(res.data || {});
            }
            $(opts.modal).modal('show');
        }).fail(function () {
            DRMS.toast('Error', 'Could not load record', 'error');
        });
    });
    $tbl.on('click', '.btn-drms-delete', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('id');
        if (!id) {
            DRMS.toast('Error', 'Could not determine record id.', 'error');
            return;
        }
        DRMS.confirm('Delete record?', opts.deleteConfirm || 'This cannot be undone.', function () {
            DRMS.postData(opts.baseUrl + '/delete/' + id, {}, function (res) {
                if (res.data && res.data.demo) {
                    DRMS.toast('Demo mode', res.message || 'Preview only — not saved to database.', 'warning');
                    return;
                }
                DRMS.toast('Removed', res.message || 'Deleted', 'success');
                if (dt) {
                    dt.ajax.reload(null, false);
                }
            });
        });
    });
    if (opts.form) {
        $(opts.form).on('submit', function (e) {
            e.preventDefault();
            var recordId = $id.val();
            var url      = recordId ? (opts.baseUrl + '/update/' + recordId) : (opts.baseUrl + '/store');
            var submitFn = opts.multipart ? DRMS.postFormData : DRMS.postForm;
            submitFn(url, this, function (res) {
                var finish = function () {
                    $(opts.modal).modal('hide');
                    DRMS.toast('Saved', res.message || 'Saved', 'success');
                    if (dt) {
                        dt.ajax.reload(null, false);
                    }
                };
                if (typeof opts.onSaved === 'function') {
                    opts.onSaved(res, recordId, finish);
                } else {
                    finish();
                }
            });
        });
    }
};
/**
 * REST API helpers — admin UI loads data via /api/v1 (inventory-style fetch/ajax).
 * Uses session cookie (same-origin) or optional JWT in localStorage.
 */
DRMS.apiExtractErrors = function (payload) {
    if (!payload) {
        return null;
    }
    if (payload.errors && typeof payload.errors === 'object') {
        return payload.errors;
    }
    if (payload.messages && typeof payload.messages === 'object') {
        var msgs = payload.messages;
        if (typeof msgs.message === 'string' && Object.keys(msgs).length === 1) {
            return msgs.message;
        }
        return msgs;
    }
    return null;
};
DRMS.apiBase = function () {
    var base = window.DRMS_API_BASE || '';
    if (!base) {
        base = window.location.origin + '/api/v1';
    }
    return base.replace(/\/$/, '');
};
DRMS.apiFetch = function (method, path, body) {
    var headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    var useJwt = !window.DRMS_API_SESSION_ONLY;
    var token = useJwt ? localStorage.getItem('drms_token') : null;
    if (token) {
        headers.Authorization = 'Bearer ' + token;
    }
    var opts = { method: method, headers: headers, credentials: 'same-origin' };
    if (body !== undefined && body !== null) {
        if (body instanceof FormData) {
            opts.body = body;
        } else {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
    }
    return fetch(DRMS.apiBase() + path, opts).then(function (res) {
        return res.text().then(function (text) {
            var json = {};
            if (text) {
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    var parseErr = new Error(
                        res.status === 401 || res.redirected
                            ? 'Session expired. Please refresh the page and log in again.'
                            : 'Server returned an invalid response. Check that you are logged in.'
                    );
                    parseErr.status = res.status;
                    throw parseErr;
                }
            }
            if (!res.ok) {
                var errors = DRMS.apiExtractErrors(json);
                var msg = json.message || json.error;
                if (!msg && errors && typeof errors === 'object') {
                    msg = Object.values(errors).join('\n');
                }
                if (!msg) {
                    msg = 'Request failed (' + res.status + ')';
                }
                var err = new Error(msg);
                err.payload = json;
                err.errors = errors;
                err.status = res.status;
                throw err;
            }
            return json;
        });
    });
};
DRMS.apiGet = function (path) { return DRMS.apiFetch('GET', path); };
DRMS.apiPost = function (path, body) { return DRMS.apiFetch('POST', path, body); };
DRMS.apiPut = function (path, body) { return DRMS.apiFetch('PUT', path, body); };
DRMS.apiDelete = function (path) { return DRMS.apiFetch('DELETE', path); };
DRMS.apiUpload = function (path, formData) {
    var headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    var useJwt = !window.DRMS_API_SESSION_ONLY;
    var token = useJwt ? localStorage.getItem('drms_token') : null;
    if (token) {
        headers.Authorization = 'Bearer ' + token;
    }
    return fetch(DRMS.apiBase() + path, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: formData
    }).then(function (res) {
        return res.text().then(function (text) {
            var json = {};
            if (text) {
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    var parseErr = new Error('Server returned an invalid response while uploading files.');
                    parseErr.status = res.status;
                    throw parseErr;
                }
            }
            if (!res.ok) {
                var errors = DRMS.apiExtractErrors(json);
                var msg = json.message || json.error;
                if (!msg && errors && typeof errors === 'object') {
                    msg = Object.values(errors).join('\n');
                }
                if (!msg) {
                    msg = 'Upload failed (' + res.status + ')';
                }
                var err = new Error(msg);
                err.payload = json;
                err.errors = errors;
                err.status = res.status;
                throw err;
            }
            return json;
        });
    });
};
DRMS.apiUnwrap = function (res) {
    return res && res.data !== undefined ? res.data : res;
};
/**
 * Submit an API form once: disable button, ignore stale responses from earlier attempts.
 */
DRMS.apiFormSubmit = function (options) {
    var $form = $(options.form);
    var seqKey = options.seqKey || String(options.form);
    DRMS._formSubmitSeq = DRMS._formSubmitSeq || {};
    DRMS._formSubmitSeq[seqKey] = (DRMS._formSubmitSeq[seqKey] || 0) + 1;
    var seq = DRMS._formSubmitSeq[seqKey];
    var $submit = $form.find('[type="submit"]');
    var submitHtml = $submit.html();
    var savingLabel = options.savingLabel || '<i class="fas fa-spinner fa-spin"></i> Saving…';

    $submit.prop('disabled', true).html(savingLabel);

    return options.request().then(function (res) {
        if (seq !== DRMS._formSubmitSeq[seqKey]) {
            return res;
        }
        if (typeof options.onSuccess === 'function') {
            options.onSuccess(res);
        }
        return res;
    }).catch(function (err) {
        if (seq !== DRMS._formSubmitSeq[seqKey]) {
            return;
        }
        if (typeof options.onError === 'function') {
            options.onError(err);
        } else if (err.errors) {
            DRMS.showErrors(err.errors);
        } else {
            DRMS.toast('Error', err.message, 'error');
        }
    }).finally(function () {
        if (seq !== DRMS._formSubmitSeq[seqKey]) {
            return;
        }
        $submit.prop('disabled', false).html(submitHtml);
    });
};
DRMS.formToObject = function (form) {
    var fd = new FormData(form);
    var obj = {};
    var skip = { csrf_test_name: true, csrf_hash: true };
    var csrf = DRMS.csrf();
    if (csrf.name) {
        skip[csrf.name] = true;
    }
    fd.forEach(function (val, key) {
        if (skip[key]) {
            return;
        }
        if (val instanceof File && (!val.name || val.size === 0)) {
            return;
        }
        if (key.endsWith('[]')) {
            var k = key.slice(0, -2);
            if (!obj[k]) {
                obj[k] = [];
            }
            obj[k].push(val);
        } else if (obj[key] !== undefined) {
            if (!Array.isArray(obj[key])) {
                obj[key] = [obj[key]];
            }
            obj[key].push(val);
        } else {
            obj[key] = val;
        }
    });
    if (obj.barangay_id === '') {
        delete obj.barangay_id;
    }
    return obj;
};
DRMS.apiDataTable = function (selector, listPath, columns, options) {
    var opts = options || {};
    if (typeof $.fn.DataTable === 'undefined') {
        return null;
    }
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    return $(selector).DataTable({
        processing: true,
        ajax: function (data, callback) {
            DRMS.apiGet(listPath).then(function (res) {
                var rows = opts.mapData ? opts.mapData(res) : (res.data || []);
                callback({ data: Array.isArray(rows) ? rows : [] });
            }).catch(function (err) {
                DRMS.toast('Data error', err.message, 'error');
                callback({ data: [] });
            });
        },
        columns: columns,
        responsive: true,
        pageLength: opts.pageLength || 25,
        order: opts.order || [[0, 'desc']]
    });
};
DRMS.bindApiCrud = function (opts) {
    var $tbl = $(opts.tableSelector);
    var dt = opts.dataTable || null;
    var $id = $(opts.idField || '#crudRecordId');
    var resource = opts.resource || '';
    if (opts.addBtn) {
        $(opts.addBtn).on('click', function () {
            $id.val('');
            if (opts.form) {
                $(opts.form)[0].reset();
            }
            if (typeof opts.onReset === 'function') {
                opts.onReset();
            }
            $(opts.modal).modal('show');
        });
    }
    $tbl.on('click', '.btn-api-edit, .btn-drms-edit', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        DRMS.apiGet(resource + '/' + id).then(function (res) {
            var d = DRMS.apiUnwrap(res);
            $id.val(id);
            if (typeof opts.onLoad === 'function') {
                opts.onLoad(d);
            }
            $(opts.modal).modal('show');
        }).catch(function (err) {
            DRMS.toast('Error', err.message, 'error');
        });
    });
    $tbl.on('click', '.btn-api-delete, .btn-drms-delete', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        DRMS.confirm(opts.deleteConfirm || 'Delete this record?', 'This cannot be undone.', function () {
            DRMS.apiDelete(resource + '/' + id).then(function (res) {
                DRMS.toast('Removed', res.message || 'Deleted', 'success');
                if (dt) {
                    dt.ajax.reload(null, false);
                }
            }).catch(function (err) {
                DRMS.toast('Error', err.message, 'error');
            });
        });
    });
    if (opts.form) {
        $(opts.form).on('submit', function (e) {
            e.preventDefault();
            var recordId = $id.val();
            var payload = typeof opts.mapPayload === 'function'
                ? opts.mapPayload(this, recordId)
                : DRMS.formToObject(this);
            DRMS.apiFormSubmit({
                form: this,
                seqKey: opts.form,
                request: function () {
                    return recordId
                        ? DRMS.apiPut(resource + '/' + recordId, payload)
                        : DRMS.apiPost(resource, payload);
                },
                onSuccess: function (res) {
                    var finish = function () {
                        $(opts.modal).modal('hide');
                        DRMS.toast('Saved', res.message || 'Saved', 'success');
                        if (dt) {
                            dt.ajax.reload(null, false);
                        }
                    };
                    if (typeof opts.onSaved === 'function') {
                        opts.onSaved(res, recordId, finish);
                    } else {
                        finish();
                    }
                }
            });
        });
    }
};
$(function () {
    $('#btn-demo-toast').on('click', function (e) {
        e.preventDefault();
        DRMS.toast('Notification', 'Volunteer deployment alert (demo)', 'info');
    });
});
