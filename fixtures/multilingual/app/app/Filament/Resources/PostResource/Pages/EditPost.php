<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\View;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Rankbeam\Seo\Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditPost extends EditRecord
{
    use Translatable;

    protected static string $resource = PostResource::class;

    public array $previewProbe = [];

    public function switchWithEarlyFormCache(string $locale): void
    {
        $this->form->getFlatComponents(withHidden: true);
        $this->updatingActiveLocale();
        $this->activeLocale = $locale;
        $this->updatedActiveLocale();
        foreach ($this->form->getFlatComponents(withHidden: true) as $component) {
            if ($component instanceof View && $component->getView() === 'seo-filament::seo-snippet-preview') {
                $this->previewProbe = $component->getViewData()['preview'];
            }
        }
    }

    protected function beforeValidate(): void
    {
        if (config('fixture.default_title') && blank($this->data['title'])) {
            $this->data['title'] = 'Generated default';
        }
    }

    protected function afterSave(): void
    {
        if (config('fixture.verify_hook_locale') && $this->record->getLocale() !== $this->activeLocale) {
            throw new \RuntimeException('Record locale leaked into afterSave');
        }
    }

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }
}
