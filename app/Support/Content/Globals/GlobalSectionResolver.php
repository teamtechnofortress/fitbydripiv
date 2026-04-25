<?php

namespace App\Support\Content\Globals;

use App\Models\GlobalSection;
use App\Support\Content\Globals\Handlers\FooterSection;
use App\Support\Content\Globals\Handlers\HeaderSection;
use Illuminate\Support\Collection;

class GlobalSectionResolver
{
    public static function resolveCollection(Collection $sections): array
    {
        return $sections
            ->mapWithKeys(fn (GlobalSection $section) => [$section->key => static::resolve($section)])
            ->all();
    }

    public static function resolve(GlobalSection $section): array
    {
        return match ($section->type) {
            'header' => HeaderSection::handle($section),
            'footer' => FooterSection::handle($section),
            default => [
                'id' => $section->id,
                'key' => $section->key,
                'type' => $section->type,
                'config' => $section->config,
            ],
        };
    }
}
