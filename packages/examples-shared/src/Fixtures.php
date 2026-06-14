<?php

declare(strict_types=1);

namespace Rankbeam\Examples;

/**
 * Loads the single-source contract fixtures (resources/contract-fixtures.json),
 * shared verbatim with the Playwright assertion library. The PHP seeder and the
 * TS tests read the SAME file, so the e2e can never drift from what is seeded.
 */
final class Fixtures
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    public static function path(): string
    {
        return __DIR__.'/../resources/contract-fixtures.json';
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $json = file_get_contents(self::path());

            if ($json === false) {
                throw new \RuntimeException('Unable to read contract fixtures at '.self::path());
            }

            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            self::$cache = is_array($decoded) ? $decoded : [];
        }

        return self::$cache;
    }

    /** @return array<string, mixed> */
    public static function config(): array
    {
        return self::all()['config'] ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function pages(): array
    {
        return self::all()['pages'] ?? [];
    }

    /**
     * The hreflang alternates for one model, absolutized against the app URL.
     * Consumed by the models' getSEOAlternates() hook so alternates flow through
     * the resolver (laravel-seo >= 3.0) — no per-page wiring in the apps.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    public static function alternatesFor(string $type, string $slug): array
    {
        foreach (self::pages() as $page) {
            if (($page['model'] ?? null) === $type
                && ($page['model_attributes']['slug'] ?? null) === $slug) {
                return array_map(
                    static fn (array $alt): array => [
                        'hreflang' => $alt['hreflang'],
                        'href' => url($alt['path']),
                    ],
                    $page['alternates'] ?? [],
                );
            }
        }

        return [];
    }
}
