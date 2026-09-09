<?php

use App\Models\Post;
use Illuminate\Contracts\Console\Kernel;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! is_file(base_path('.rankbeam-multilingual-fixture')) || ! app()->environment('local')) {
    throw new RuntimeException('Fixture only');
}
$post = Post::findOrFail(1);
foreach ($post->getTranslations('title') as $locale => $title) {
    $post->saveSEO(['title' => $title, 'description' => $post->getTranslation('content', $locale, false)], $locale);
}
echo "Restored seeded post 1 metadata in isolated fixture\n";
