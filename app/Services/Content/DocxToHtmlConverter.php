<?php

namespace App\Services\Content;

use App\Data\Content\DocumentConversionResult;
use DOMDocument;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML as HtmlWriter;
use RuntimeException;
use Throwable;

final class DocxToHtmlConverter
{
    public function __construct(
        private readonly HtmlSanitizer $htmlSanitizer,
    ) {}

    public function convert(UploadedFile $document): DocumentConversionResult
    {
        $sourcePath = $document->getRealPath();

        if ($sourcePath === false || ! is_file($sourcePath)) {
            throw new RuntimeException('The uploaded document could not be accessed.');
        }

        $temporaryHtmlPath = tempnam(sys_get_temp_dir(), 'docx_html_');

        if ($temporaryHtmlPath === false) {
            throw new RuntimeException('A temporary conversion file could not be created.');
        }

        try {
            $phpWord = IOFactory::load($sourcePath, 'Word2007');
            $writer = new HtmlWriter($phpWord);
            $writer->save($temporaryHtmlPath);

            $generatedHtml = file_get_contents($temporaryHtmlPath);

            if ($generatedHtml === false) {
                throw new RuntimeException('The converted HTML could not be read.');
            }

            $bodyHtml = $this->extractBodyContent($generatedHtml);
            $sanitizedHtml = $this->htmlSanitizer->sanitize($bodyHtml);

            if ($sanitizedHtml === '') {
                throw new RuntimeException('The document did not contain convertible content.');
            }

            return new DocumentConversionResult(
                html: $sanitizedHtml,
                sourceFilename: $document->getClientOriginalName(),
            );
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('The DOCX document could not be converted.', previous: $exception);
        } finally {
            if (is_file($temporaryHtmlPath)) {
                @unlink($temporaryHtmlPath);
            }
        }
    }

    private function extractBodyContent(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);

        try {
            $loaded = $dom->loadHTML(
                '<?xml encoding="UTF-8">'.$html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            if ($loaded === false) {
                return $html;
            }

            $body = $dom->getElementsByTagName('body')->item(0);

            if ($body === null) {
                return $html;
            }

            $content = '';

            foreach ($body->childNodes as $childNode) {
                $content .= $dom->saveHTML($childNode);
            }

            return $content;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }
    }
}
