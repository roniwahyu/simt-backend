<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class UpdateNisnBindex extends Command
{
    protected $signature = 'students:update-nisn-bindex';
    protected $description = 'Update nisn_bindex for existing students';

    public function handle(): int
    {
        $count = 0;
        
        Student::whereNotNull('nisn')
            ->whereNull('nisn_bindex')
            ->each(function ($student) use (&$count) {
                $student->nisn_bindex = hash_hmac('sha256', $student->nisn, config('app.key'));
                $student->saveQuietly();
                $count++;
            });

        $this->info("Updated {$count} students with nisn_bindex.");
        
        return Command::SUCCESS;
    }
}
