<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'editor@example.test'], ['name' => 'Fixture editor', 'password' => Hash::make('Local-fixture-only-123!')]);
        $titles = ['en' => 'Coffee at home', 'it' => 'Il caffè a casa', 'tr' => 'Evde kahve', 'ja' => '自宅で淹れるコーヒー', 'zh_CN' => '在家冲泡咖啡', 'zh_TW' => '在家沖泡咖啡'];
        $slugs = ['en' => 'coffee-at-home', 'it' => 'caffè-a-casa', 'tr' => 'evde-kahve', 'ja' => '自宅のコーヒー', 'zh_CN' => '家庭咖啡', 'zh_TW' => '家庭咖啡'];
        $content = [
            'en' => 'Choose a grinder that lets you adjust the grind. Start with fresh beans and measure the water for each cup. Change one setting at a time and write down what tastes better.',
            'it' => 'Scegli un macinacaffè che permetta di regolare la macinatura. Parti da chicchi freschi e misura l’acqua per ogni tazza. Cambia una sola impostazione alla volta e annota il risultato.',
            'tr' => 'Öğütme ayarını değiştirebileceğiniz bir değirmen seçin. Taze çekirdeklerle başlayın ve her fincan için suyu ölçün. Her denemede yalnızca bir ayarı değiştirin ve sonucu not edin.',
            'ja' => '挽き目を調整できるミルを選びましょう。新鮮な豆を使い、一杯ごとに水の量を量ります。一度に変える設定は一つにして、味の変化を記録してください。',
            'zh_CN' => '选择可以调节研磨粗细的磨豆机。使用新鲜咖啡豆，每次冲泡都称量水的用量。每次只调整一个设置，并记录口感的变化。',
            'zh_TW' => '選擇可以調整研磨粗細的磨豆機。使用新鮮咖啡豆，每次沖泡都秤量水的用量。每次只調整一個設定，並記錄口感的變化。',
        ];
        if (! Post::query()->exists()) {
            $post = Post::query()->create(['title' => $titles, 'slug' => $slugs, 'content' => $content]);
            foreach ($titles as $locale => $title) {
                $post->saveSEO(['title' => $title, 'description' => $content[$locale]], $locale);
            }
            Post::query()->create([
                'title' => ['en' => 'A second coffee guide', 'it' => 'Una seconda guida al caffè'],
                'slug' => ['en' => 'second-guide', 'it' => 'seconda-guida'],
                'content' => ['en' => 'This is a separate article for checking record isolation.', 'it' => 'Questo articolo separato serve a verificare che i dati non passino da una pagina all’altra.'],
            ]);
        }
    }
}
