<?php

use App\Models\Student;
use Illuminate\Support\Facades\Hash;

$count = 0;
Student::whereNotNull('nisn')->whereNull('nisn_bindex')->each(function ($student) use (&$count) {
    $student->nisn_bindex = hash_hmac('sha256', $student->nisn, config('app.key'));
    $student->saveQuietly();
    $count++;
});

echo "Updated {$count} students with nisn_bindex.\n";
