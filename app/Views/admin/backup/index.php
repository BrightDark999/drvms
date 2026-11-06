<?= $this->extend('layouts/admin') ?>

<?= $this->section('content_header') ?>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0"><i class="fas fa-database mr-2 text-danger"></i>Database Backup</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Database Backup</li>
            </ol>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-danger"><i class="fas fa-database"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Database Name</span>
                    <span class="info-box-number"><?= esc($dbName) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-info"><i class="fas fa-table"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Tables</span>
                    <span class="info-box-number"><?= (int) $tableCount ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-success"><i class="fas fa-list-ol"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Records</span>
                    <span class="info-box-number"><?= number_format($totalRows) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-secondary"><i class="fas fa-server"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Database Host</span>
                    <span class="info-box-number text-truncate" title="<?= esc($dbHost) ?>"><?= esc($dbHost) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-download mr-1"></i> Create & Download Instant Backup</h3>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        Generate a complete, self-contained SQL backup containing all schema definitions, table structures, and data rows.
                        You can download and store this file safely for your weekly DRMS disaster recovery archiving.
                    </p>
                    <a href="<?= base_url('admin/backup/download') ?>" class="btn btn-danger btn-lg shadow-sm" id="btnDownloadBackup">
                        <i class="fas fa-file-download mr-2"></i> Download Full SQL Backup (.sql)
                    </a>
                    <hr>
                    <h5 class="font-weight-bold"><i class="fas fa-shield-alt text-danger mr-1"></i> Backup & Archival Guidelines</h5>
                    <ul class="text-muted small pl-3 mb-0">
                        <li><strong>Weekly Frequency:</strong> It is recommended to download a full SQL backup at the end of each operational week.</li>
                        <li><strong>Safe Storage:</strong> Keep the downloaded <code>.sql</code> archive in a secure, encrypted offline storage or secondary cloud backup.</li>
                        <li><strong>Restore Compatibility:</strong> The output format is compatible with phpMyAdmin, MySQL Workbench, and standard MySQL CLI importers.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-th-list mr-1"></i> Database Tables Overview</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Table Name</th>
                                <th class="text-right">Record Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $idx => $t): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><code><?= esc($t['name']) ?></code></td>
                                <td class="text-right"><span class="badge badge-secondary"><?= number_format((int) $t['rows']) ?></span></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-clock mr-1"></i> Automated Weekly Backups</h3>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        For production environments, you can also set up a scheduled weekly cron task or host-level backup schedule using the automated CLI or web cron endpoint.
                    </p>
                    <div class="p-2 bg-light border rounded small mb-3">
                        <strong>Weekly Cron Schedule:</strong><br>
                        <code>0 2 * * 0 /usr/bin/mysqldump -u [user] -p[pass] [dbname] &gt; /backups/drms_weekly.sql</code>
                    </div>
                    <p class="small text-muted mb-0">
                        All manual backup downloads are recorded in the <strong>Audit Trail</strong> for administrative compliance.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
