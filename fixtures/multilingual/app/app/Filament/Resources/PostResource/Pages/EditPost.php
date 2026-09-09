<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Rankbeam\Seo\Filament\Resources\Pages\EditRecord\Concerns\Translatable;

class EditPost extends EditRecord
{
    use Translatable;

    protected static string $resource = PostResource::class;

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
