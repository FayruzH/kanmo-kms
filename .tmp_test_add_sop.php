<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopDocument;
use App\Models\User;

$category = SopCategory::query()->where('active', true)->first();
$department = SopDepartment::query()->where('active', true)->first();
$pic = User::query()->where('active', true)->first();

if (!$category || !$department || !$pic) {
    echo "MISSING_MASTER\n";
    exit;
}

try {
    $doc = SopDocument::query()->create([
        'title' => 'TMP TEST ADD '.date('YmdHis'),
        'category_id' => $category->id,
        'department_id' => $department->id,
        'type' => 'url',
        'url' => 'https://example.com',
        'version' => 'v1.0',
        'effective_date' => date('Y-m-d'),
        'expiry_date' => date('Y-m-d', strtotime('+365 days')),
        'pic_user_id' => $pic->id,
        'status' => 'active',
    ]);

    echo "CREATE_OK_ID={$doc->id}\n";
    $doc->delete();
    echo "DELETE_OK\n";
} catch (Throwable $e) {
    echo "CREATE_FAIL: ".$e->getMessage()."\n";
}
