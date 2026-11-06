<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use CodeIgniter\HTTP\ResponseInterface;

class Backup extends BaseController
{
    public function index(): string
    {
        helper('drms');
        drms_require_permission('admin.*');

        $db = \Config\Database::connect();
        $tables = $db->listTables();
        $tableStats = [];
        $totalRows = 0;

        foreach ($tables as $table) {
            $count = $db->table($table)->countAllResults();
            $totalRows += $count;
            $tableStats[] = [
                'name'  => $table,
                'rows'  => $count,
            ];
        }

        return view('admin/backup/index', [
            'title'      => 'Database Backup',
            'drms'       => drms_config(),
            'dbName'     => $db->getDatabase(),
            'dbHost'     => $db->hostname,
            'tableCount' => count($tables),
            'totalRows'  => $totalRows,
            'tables'     => $tableStats,
        ]);
    }

    public function download(): ResponseInterface
    {
        helper('drms');
        drms_require_permission('admin.*');

        $db = \Config\Database::connect();
        $databaseName = $db->getDatabase();
        $tables = $db->listTables();
        $now = date('Y-m-d H:i:s');
        $dateSlug = date('Y-m-d_His');

        $dump = "-- ========================================================\n";
        $dump .= "-- DRMS Database Backup\n";
        $dump .= "-- Database: {$databaseName}\n";
        $dump .= "-- Generated: {$now} (Asia/Manila)\n";
        $dump .= "-- Host: {$db->hostname}\n";
        $dump .= "-- ========================================================\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $dump .= "SET NAMES utf8mb4;\n\n";

        foreach ($tables as $table) {
            // Drop & Create Table Structure
            $dump .= "-- --------------------------------------------------------\n";
            $dump .= "-- Table structure for table `{$table}`\n";
            $dump .= "-- --------------------------------------------------------\n";
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            if (isset($createRow['Create Table'])) {
                $dump .= $createRow['Create Table'] . ";\n\n";
            }

            // Table Data
            $dump .= "-- Dumping data for table `{$table}`\n";
            $rows = $db->table($table)->get()->getResultArray();
            if ($rows !== []) {
                $dump .= "INSERT INTO `{$table}` VALUES \n";
                $rowLines = [];
                foreach ($rows as $r) {
                    $values = array_map(function ($val) use ($db) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $db->escape($val);
                    }, $r);
                    $rowLines[] = '(' . implode(', ', $values) . ')';
                }
                $dump .= implode(",\n", $rowLines) . ";\n\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $dump .= "-- Backup Complete\n";

        try {
            model(AuditLogModel::class)->insert([
                'user_id'     => auth()->id(),
                'action'      => 'backup.download',
                'object_type' => 'database',
                'object_id'   => null,
                'details'     => "Full SQL backup generated for {$databaseName} (" . count($tables) . " tables, {$now})",
            ]);
        } catch (\Throwable) {
            // Ignore audit log error if any
        }

        $filename = "drms_backup_{$dateSlug}.sql";

        return $this->response
            ->setHeader('Content-Type', 'application/sql')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dump);
    }
}
