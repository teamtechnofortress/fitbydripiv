<?php

namespace App\Services\Content\Handlers;

use App\enums\SectionType;
use App\Models\PageSection;

class SectionHandlerFactory
{
    public static function handle(PageSection $section, array $data): void
    {
        match ($section->type) {
            SectionType::FAQ => FaqSectionHandler::handle($section, $data),
            default => static::cleanup($section),
        };
    }

    protected static function cleanup(PageSection $section): void
    {
        $section->faqs()->delete();
    }
}
