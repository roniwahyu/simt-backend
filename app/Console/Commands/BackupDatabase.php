<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'simt:backup-db';
    protected $description = 'Melakukan backup database MySQL terkompresi (.sql.gz) dan menghapus backup berusia >14 hari';

    public function handle()
    {
        $this->info('Memulai backup database...');
        Log::info('Console: Memulai backup database.');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'simt_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupDir . '/' . $filename;

        // Siapkan perintah mysqldump
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Khusus Windows, berikan tanda kutip untuk password jika ada spasi/char khusus
            $passArg = $password !== '' ? "--password=\"{$password}\"" : '';
            $command = "mysqldump --host={$host} --port={$port} --user={$username} {$passArg} {$database} > \"{$filePath}\"";
        } else {
            $passArg = $password !== '' ? '--password=' . escapeshellarg($password) : '';
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $passArg,
                escapeshellarg($database),
                escapeshellarg($filePath)
            );
        }

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error('Gagal mengeksekusi mysqldump. Kode error: ' . $returnVar);
            Log::error('Console: Backup database gagal. returnVar=' . $returnVar);
            return 1;
        }

        if (!file_exists($filePath) || filesize($filePath) === 0) {
            $this->error('File backup kosong atau tidak terbentuk.');
            Log::error('Console: Backup database gagal. File kosong/tidak terbentuk.');
            return 1;
        }

        // Kompresi file menggunakan gzip di PHP
        $gzFilePath = $filePath . '.gz';
        $data = file_get_contents($filePath);
        $gzdata = gzencode($data, 9);
        file_put_contents($gzFilePath, $gzdata);

        // Hapus file sql mentah
        unlink($filePath);

        $this->info("Backup berhasil disimpan di: {$gzFilePath}");
        Log::info("Console: Backup database sukses. File: {$gzFilePath}");

        // Pruning backup lama (>14 hari)
        $this->pruneOldBackups($backupDir);

        return 0;
    }

    protected function pruneOldBackups(string $backupDir): void
    {
        $files = glob($backupDir . '/*.sql.gz');
        $now = time();
        $retentionSec = 14 * 24 * 60 * 60; // 14 hari

        $prunedCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) > $retentionSec) {
                    unlink($file);
                    $prunedCount++;
                }
            }
        }

        if ($prunedCount > 0) {
            $this->info("Berhasil menghapus {$prunedCount} file backup lama.");
            Log::info("Console: Pruned {$prunedCount} old backups.");
        }
    }
}
