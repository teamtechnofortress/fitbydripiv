<?php

namespace App\Data\Content;

final readonly class DocumentConversionResult
{
    public function __construct(
        public string $html,
        public string $sourceFilename,
        public array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'html' => $this->html,
            'source' => 'docx_import',
            'source_filename' => $this->sourceFilename,
            'warnings' => $this->warnings,
        ];
    }
}
