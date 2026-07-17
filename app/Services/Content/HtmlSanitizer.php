<?php

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

final class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $cachePath = storage_path('framework/cache/htmlpurifier');

        File::ensureDirectoryExists($cachePath);

        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set(
            'HTML.Allowed',
            implode(',', [
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'p',
                'br',
                'strong',
                'b',
                'em',
                'i',
                'u',
                's',
                'blockquote',
                'ul',
                'ol',
                'li',
                'a[href|title|target|rel]',
                'table',
                'thead',
                'tbody',
                'tfoot',
                'tr',
                'th[colspan|rowspan]',
                'td[colspan|rowspan]',
                'hr',
                'sub',
                'sup',
                'span',
            ])
        );
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.DisableExternalResources', true);
        $config->set('URI.DisableResources', true);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(string $html): string
    {
        return trim($this->purifier->purify($html));
    }
}
