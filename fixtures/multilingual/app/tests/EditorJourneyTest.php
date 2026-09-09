<?php

use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages\LegacyEditPost;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Rankbeam\Seo\Models\SEOMeta;
use Rankbeam\Seo\Services\LlmsTxt\LlmsTxtBuilder;
use Rankbeam\Seo\Services\Sitemap\SitemapBuilder;

class EditorJourneyTest extends TestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(Kernel::class)->bootstrap();
        DB::beginTransaction();
        $this->actingAs(User::query()->firstOrFail());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function fixturePost(): Post
    {
        $post = Post::query()->create([
            'title' => ['en' => 'English article', 'it' => 'Articolo italiano', 'ja' => '日本語の記事'],
            'slug' => ['en' => 'integration-en', 'it' => 'integration-it', 'ja' => 'integration-ja'],
            'content' => ['en' => 'An English article for the editor test.', 'it' => 'Un articolo italiano per la prova.', 'ja' => '編集画面を確認するための日本語の記事です。'],
        ]);
        foreach (['en' => 'English SEO title', 'it' => 'Titolo SEO italiano', 'ja' => '日本語のSEOタイトル'] as $locale => $title) {
            $post->saveSEO(['title' => $title], $locale);
        }

        return $post;
    }

    public function test_real_locale_switch_does_not_write_other_languages(): void
    {
        $post = $this->fixturePost();
        $before = $post->seoMetaForLocale('it')->firstOrFail()->title;
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('activeLocale', 'it');
        $this->assertSame($before, $post->seoMetaForLocale('it')->firstOrFail()->title, 'Switching languages must not write metadata.');
        $editor->assertSet('data.seo_meta.title', 'Titolo SEO italiano');
        $editor->set('activeLocale', 'ja')->assertSet('data.seo_meta.title', '日本語のSEOタイトル');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_drafts_round_trip_and_one_save_persists_all_visited_locales(): void
    {
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.seo_meta.title', 'English draft')
            ->set('activeLocale', 'it')
            ->set('data.seo_meta.title', 'Bozza italiana')
            ->set('data.title', 'Articolo aggiornato')
            ->set('activeLocale', 'ja')
            ->set('data.seo_meta.title', '日本語の下書き')
            ->set('activeLocale', 'en')
            ->assertSet('data.seo_meta.title', 'English draft');
        $this->assertSame('English SEO title', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $editor->call('save', false, false)->assertHasNoErrors();
        foreach (['en' => 'English draft', 'it' => 'Bozza italiana', 'ja' => '日本語の下書き'] as $locale => $title) {
            $this->assertSame($title, $post->seoMetaForLocale($locale)->firstOrFail()->title);
        }
        $this->assertSame('Articolo aggiornato', $post->fresh()->getTranslation('title', 'it'));
        $editor->assertSet('seoLocaleDrafts', []);
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('activeLocale', 'ja')->assertSet('data.seo_meta.title', '日本語の下書き');
    }

    public function test_invalid_inactive_draft_blocks_every_write(): void
    {
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.seo_meta.title', 'English draft')->set('activeLocale', 'it')
            ->set('data.seo_meta.canonical', 'not a URL')->set('activeLocale', 'en');
        $editor->call('save', false, false)->assertHasErrors(['data.seo_meta.canonical'])
            ->assertSet('activeLocale', 'it')->assertSet('data.seo_meta.canonical', 'not a URL');
        $this->assertSame('English SEO title', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Titolo SEO italiano', $post->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_create_persists_metadata_for_both_drafted_languages(): void
    {
        $editor = Livewire::test(CreatePost::class);
        $editor->set('data.title', 'New English article')->set('data.slug', 'new-english-article')
            ->set('data.content', 'English content')->set('data.seo_meta.title', 'New English SEO')
            ->set('activeLocale', 'it')
            ->set('data.title', 'Nuovo articolo')->set('data.slug', 'nuovo-articolo')
            ->set('data.content', 'Contenuto italiano')->set('data.seo_meta.title', 'Nuovo SEO italiano')
            ->call('create')->assertHasNoErrors();
        $post = Post::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('New English article', $post->getTranslation('title', 'en'));
        $this->assertSame('Nuovo articolo', $post->getTranslation('title', 'it'));
        $this->assertSame('New English SEO', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Nuovo SEO italiano', $post->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_upstream_page_guard_prevents_wrong_locale_writes(): void
    {
        $post = $this->fixturePost();
        Livewire::test(LegacyEditPost::class, ['record' => $post->getKey()])
            ->set('activeLocale', 'it')->assertSet('data.seo_meta.title', 'Titolo SEO italiano');
        $this->assertSame('Titolo SEO italiano', $post->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_inactive_parent_validation_shows_correct_locale_and_can_retry(): void
    {
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.seo_meta.title', 'English draft')->set('activeLocale', 'it')
            ->set('data.title', '')->set('activeLocale', 'en')
            ->call('save', false, false)->assertHasErrors(['data.title'])
            ->assertSet('activeLocale', 'it')->assertSet('data.title', '');
        $this->assertSame('English SEO title', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $editor->set('data.title', 'Corretto')->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('English draft', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Corretto', $post->fresh()->getTranslation('title', 'it'));
    }

    public function test_pending_upload_survives_switch_and_only_stores_on_save(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public', 'filament.default_filesystem_disk' => 'public']);
        $post = $this->fixturePost();
        $file = UploadedFile::fake()->image('italian-cover.png', 1200, 630);
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('activeLocale', 'it')->set('data.seo_meta.og_image', [$file])
            ->set('activeLocale', 'ja')->set('activeLocale', 'it');
        $this->assertCount(0, Storage::disk('public')->files('seo'));
        $state = $editor->get('data.seo_meta.og_image');
        $this->assertCount(1, $state);
        $this->assertInstanceOf(TemporaryUploadedFile::class, array_values($state)[0]);
        $editor->set('activeLocale', 'en')->call('save', false, false)->assertHasNoErrors();
        $path = $post->seoMetaForLocale('it')->firstOrFail()->og_image;
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
        $this->assertNull($post->seoMetaForLocale('en')->firstOrFail()->og_image);
        $this->assertCount(1, Storage::disk('public')->files('seo'));
    }

    public function test_schema_draft_and_concurrent_document_survive_locale_round_trip(): void
    {
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('activeLocale', 'it')->set('data.seo_schema.blocks', [
            'stable-block-id' => ['type' => 'faq', 'questions' => [
                'stable-question-id' => ['question' => 'Come funziona?', 'answer' => 'Si salva per lingua.'],
            ]],
        ])->set('activeLocale', 'ja')->set('activeLocale', 'it');
        $this->assertArrayHasKey('stable-block-id', $editor->get('data.seo_schema.blocks'));
        $external = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => 'Documento esterno'];
        $post->seoMetaForLocale('it')->firstOrFail()->update(['schema_jsonld' => [$external]]);
        $editor->set('activeLocale', 'en')->call('save', false, false)->assertHasNoErrors();
        $stored = $post->seoMetaForLocale('it')->firstOrFail()->schema_jsonld;
        $this->assertContains($external, $stored);
        $faq = collect($stored)->firstWhere('@type', 'FAQPage');
        $this->assertSame('Come funziona?', $faq['mainEntity'][0]['name']);
        $this->assertNull($post->seoMetaForLocale('en')->firstOrFail()->schema_jsonld);
    }

    public function test_inactive_write_failure_rolls_back_and_keeps_retry_drafts(): void
    {
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.seo_meta.title', 'English draft')->set('activeLocale', 'it')
            ->set('data.seo_meta.title', 'Bozza italiana')->set('activeLocale', 'en');
        $fail = true;
        SEOMeta::saving(function ($meta) use (&$fail) {
            if ($fail && $meta->locale === 'it') {
                throw new RuntimeException('Fixture write failure');
            }
        });
        try {
            $editor->call('save', false, false);
            $this->fail('Expected the injected write failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Fixture write failure', $exception->getMessage());
        }
        $this->assertSame('English SEO title', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Titolo SEO italiano', $post->seoMetaForLocale('it')->firstOrFail()->title);
        $fail = false;
        $editor->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('English draft', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Bozza italiana', $post->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_create_another_has_no_previous_record_drafts(): void
    {
        $editor = Livewire::test(CreatePost::class);
        $editor->set('data.title', 'First English')->set('data.slug', 'first-en')
            ->set('data.content', 'First English content')->set('data.seo_meta.title', 'First SEO')
            ->set('activeLocale', 'it')->set('data.title', 'Primo articolo')->set('data.slug', 'primo')
            ->set('data.content', 'Primo contenuto')->set('data.seo_meta.title', 'Primo SEO')
            ->call('create', true)->assertHasNoErrors()
            ->assertSet('seoLocaleDrafts', [])->assertSet('otherLocaleData', []);
        $editor->set('data.title', 'Secondo articolo')->set('data.slug', 'secondo')
            ->set('data.content', 'Secondo contenuto')->set('data.seo_meta.title', 'Secondo SEO')
            ->call('create')->assertHasNoErrors();
        $post = Post::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('Secondo SEO', $post->seoMetaForLocale('it')->firstOrFail()->title);
        $this->assertFalse($post->seoMetaForLocale('en')->exists());
        $this->assertFalse($post->hasTranslation('title', 'en'));
    }

    public function test_clear_a_translation_does_not_clear_another_records_or_languages(): void
    {
        $post = $this->fixturePost();
        $other = $this->fixturePost();
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('activeLocale', 'it')->set('data.seo_meta.title', '')
            ->set('activeLocale', 'en')->call('save', false, false)->assertHasNoErrors();
        $this->assertNull($post->seoMetaForLocale('it')->firstOrFail()->title);
        $this->assertSame('English SEO title', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Titolo SEO italiano', $other->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_explicit_locale_tabs_remain_shared_across_the_page_switcher(): void
    {
        config(['fixture.explicit_locales' => ['en', 'it']]);
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.seo_meta.en.title', 'English tab draft')
            ->set('data.seo_meta.it.title', 'Bozza nella scheda')->set('activeLocale', 'ja')
            ->assertSet('data.seo_meta.en.title', 'English tab draft')
            ->assertSet('data.seo_meta.it.title', 'Bozza nella scheda')
            ->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('English tab draft', $post->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Bozza nella scheda', $post->seoMetaForLocale('it')->firstOrFail()->title);
        $this->assertSame('日本語のSEOタイトル', $post->seoMetaForLocale('ja')->firstOrFail()->title);
    }

    public function test_separate_related_translation_models_keep_their_own_drafts(): void
    {
        config(['fixture.related_translations' => true]);
        $post = $this->fixturePost();
        $rows = [];
        foreach (['en', 'it', 'ja'] as $locale) {
            $rows[$locale] = PostTranslation::query()->create([
                'post_id' => $post->id, 'locale' => $locale, 'title' => $post->getTranslation('title', $locale),
                'slug' => 'related-'.$locale, 'content' => $post->getTranslation('content', $locale),
            ]);
            $rows[$locale]->saveSEO(['title' => 'Related '.$locale], $locale);
        }
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->assertSet('data.seo_meta.title', 'Related en')->set('activeLocale', 'it')
            ->assertSet('data.seo_meta.title', 'Related it')->set('data.seo_meta.title', 'SEO collegato')
            ->set('activeLocale', 'ja')->assertSet('data.seo_meta.title', 'Related ja')
            ->set('activeLocale', 'en')->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('SEO collegato', $rows['it']->seoMetaForLocale('it')->firstOrFail()->title);
        $this->assertFalse($rows['it']->seoMetaForLocale('en')->exists());
        $this->assertSame('Related en', $rows['en']->seoMetaForLocale('en')->firstOrFail()->title);
        $this->assertSame('Titolo SEO italiano', $post->seoMetaForLocale('it')->firstOrFail()->title);
    }

    public function test_inactive_parent_dehydration_matches_active_locale_and_hook_locale_is_restored(): void
    {
        config(['fixture.uppercase_titles' => true, 'fixture.verify_hook_locale' => true]);
        $post = $this->fixturePost();
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('data.title', 'english draft')->set('activeLocale', 'it')
            ->set('data.title', 'bozza italiana')->set('activeLocale', 'en')
            ->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('ENGLISH DRAFT', $post->fresh()->getTranslation('title', 'en'));
        $this->assertSame('BOZZA ITALIANA', $post->fresh()->getTranslation('title', 'it'));
    }

    public function test_explicit_tabs_without_schema_still_validate_inactive_parent_fields(): void
    {
        config(['fixture.explicit_locales' => ['en', 'it'], 'fixture.without_schema' => true]);
        $post = $this->fixturePost();
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('activeLocale', 'it')->set('data.title', '')->set('activeLocale', 'en')
            ->call('save', false, false)->assertHasErrors(['data.title'])->assertSet('activeLocale', 'it');
        $this->assertSame('Articolo italiano', $post->fresh()->getTranslation('title', 'it'));
    }

    public function test_inactive_upload_remains_retryable_after_persistence_rejection(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public', 'filament.default_filesystem_disk' => 'public']);
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('activeLocale', 'it')->set('data.seo_meta.og_image', [
            UploadedFile::fake()->image('cover.png', 1200, 630),
        ])->set('activeLocale', 'en');
        $fail = true;
        SEOMeta::saving(function ($meta) use (&$fail) {
            if ($fail && $meta->locale === 'it') {
                throw ValidationException::withMessages(['data.seo_meta.og_image' => 'Fixture persistence rejection']);
            }
        });
        $editor->call('save', false, false)->assertHasErrors(['data.seo_meta.og_image']);
        $this->assertNull($post->seoMetaForLocale('it')->firstOrFail()->og_image);
        $fail = false;
        $editor->call('save', false, false)->assertHasNoErrors();
        $path = $post->seoMetaForLocale('it')->firstOrFail()->og_image;
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->files('seo'));
    }

    public function test_shared_upload_draft_survives_locale_hydration(): void
    {
        config(['fixture.shared_upload' => true]);
        $post = $this->fixturePost();
        $editor = Livewire::test(EditPost::class, ['record' => $post->getKey()]);
        $editor->set('data.cover', [UploadedFile::fake()->image('shared.png')])
            ->set('activeLocale', 'it')->set('activeLocale', 'en');
        $this->assertInstanceOf(TemporaryUploadedFile::class, array_values($editor->get('data.cover'))[0]);
    }

    public function test_application_before_validate_hook_runs_before_draft_validation(): void
    {
        config(['fixture.default_title' => true]);
        $post = $this->fixturePost();
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('data.title', '')->call('save', false, false)->assertHasNoErrors();
        $this->assertSame('Generated default', $post->fresh()->getTranslation('title', 'en'));
    }

    public function test_public_unicode_routes_metadata_and_alternates_agree_with_saved_locales(): void
    {
        config(['seo.cache.resolver.enabled' => true]);
        $post = Post::findOrFail(1);
        foreach ($post->getTranslations('slug') as $locale => $slug) {
            $url = url('/'.$locale.'/posts/'.rawurlencode($slug));
            $response = $this->get($url)->assertOk();
            $title = $post->seoMetaForLocale($locale)->firstOrFail()->title;
            $response->assertSee('<title>'.e($title).'</title>', false);
            $doc = new DOMDocument;
            @$doc->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new DOMXPath($doc);
            $this->assertSame(str_replace('_', '-', $locale), $xpath->evaluate('string(//html/@lang)'));
            $this->assertSame($url, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertSame($title, $xpath->evaluate('string(//meta[@property="og:title"]/@content)'));
            foreach ($post->getTranslations('slug') as $otherLocale => $otherSlug) {
                $code = str_replace('_', '-', $otherLocale);
                $this->assertSame(url('/'.$otherLocale.'/posts/'.rawurlencode($otherSlug)), $xpath->evaluate('string(//link[@hreflang="'.$code.'"]/@href)'));
            }
            $this->assertSame('en', app()->getLocale());
        }
        $second = Post::findOrFail(2);
        $this->get(url('/ja/posts/'.rawurlencode($second->getTranslation('slug', 'en'))))->assertNotFound();
    }

    public function test_sitemap_and_llms_index_link_only_actual_translations(): void
    {
        config(['seo.sitemap.alternates' => true, 'seo.llms_txt.alternates' => true]);
        $xml = app(SitemapBuilder::class)->build()->render();
        $markdown = app(LlmsTxtBuilder::class)->build();
        $post = Post::findOrFail(1);
        foreach ($post->getTranslations('slug') as $locale => $slug) {
            $url = url('/'.$locale.'/posts/'.rawurlencode($slug));
            $this->assertStringContainsString($url, $xml);
            $this->assertStringContainsString($url, $markdown);
        }
        $this->assertStringNotContainsString('/ja/posts/second-guide', $xml);
        $this->assertStringNotContainsString('/ja/posts/second-guide', $markdown);
    }
}
