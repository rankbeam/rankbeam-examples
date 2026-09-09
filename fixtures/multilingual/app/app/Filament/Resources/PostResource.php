<?php

namespace App\Filament\Resources;

use App\Models\Post;
use App\Models\PostTranslation;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Rankbeam\Seo\Filament\Concerns\HasSEOFields;

class PostResource extends Resource
{
    use HasSEOFields;
    use Translatable;

    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        $target = config('fixture.related_translations')
            ? fn (?Post $record, $livewire) => $record ? PostTranslation::query()
                ->where('post_id', $record->id)->where('locale', $livewire->activeLocale)->first() : null
            : null;

        return $schema->components([
            TextInput::make('title')->label('Article title')->required()->maxLength(160)
                ->dehydrateStateUsing(fn ($state) => config('fixture.uppercase_titles') ? mb_strtoupper($state) : $state),
            TextInput::make('slug')->required()->maxLength(160),
            Textarea::make('content')->label('Article content')->required()->rows(4),
            ...(config('fixture.shared_upload') ? [FileUpload::make('cover')->dehydrated(false)] : []),
            static::seoSection(target: $target, locales: config('fixture.explicit_locales')),
            ...(config('fixture.without_schema') ? [] : [static::seoSchemaSection(target: $target)]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('id'), TextColumn::make('title')])
            ->recordUrl(fn (Post $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => PostResource\Pages\ListPosts::route('/'),
            'create' => PostResource\Pages\CreatePost::route('/create'),
            'edit' => PostResource\Pages\EditPost::route('/{record}/edit'),
            'legacy-edit' => PostResource\Pages\LegacyEditPost::route('/{record}/legacy-edit'),
        ];
    }
}
