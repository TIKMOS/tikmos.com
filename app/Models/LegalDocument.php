<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property bool $is_published
 * @property int $version
 */
#[Fillable(['key', 'title', 'body', 'version', 'is_published'])]
class LegalDocument extends Model
{
    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /**
     * Parse le HTML du document : extrait les <h2> pour le sommaire et leur ajoute une ancre.
     *
     * @return array{toc: array<int, array{id: string, title: string}>, html: string}
     */
    public function parsed(): array
    {
        $html = trim((string) $this->body);

        if ($html === '') {
            return ['toc' => [], 'html' => ''];
        }

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><div id="sz-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $toc = [];
        $i = 0;
        foreach ($doc->getElementsByTagName('h2') as $h2) {
            $i++;
            $title = trim($h2->textContent);
            $id = 'sec-'.$i.'-'.Str::slug($title);
            $h2->setAttribute('id', $id);
            $h2->setAttribute('class', 'scroll-mt-28');
            $toc[] = ['id' => $id, 'title' => $title];
        }

        $root = $doc->getElementById('sz-root');
        $body = '';
        if ($root !== null) {
            foreach ($root->childNodes as $child) {
                $body .= (string) $doc->saveHTML($child);
            }
        }

        return ['toc' => $toc, 'html' => $body];
    }
}
