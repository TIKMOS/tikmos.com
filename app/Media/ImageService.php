<?php

declare(strict_types=1);

namespace App\Media;

use App\Settings\MediaSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Point d'entrée unique pour manipuler les images : validation homogène,
 * attache avec texte alternatif, réordonnancement, et purge des orphelins.
 */
class ImageService
{
    public function __construct(private MediaSettings $settings) {}

    /**
     * Règles de validation Livewire pour un champ d'upload multiple.
     *
     * @return array<string, mixed>
     */
    public function rules(string $field, ?int $max = null): array
    {
        $max ??= $this->settings->gallery_max;

        return [
            $field => ['array', 'max:'.$max],
            $field.'.*' => ['image', $this->settings->mimesRule(), 'max:'.$this->settings->max_size_kb],
        ];
    }

    /**
     * Règles de validation pour un fichier image unique (upload inline éditeur).
     *
     * @return array<int, string>
     */
    public function imageRules(): array
    {
        return ['image', $this->settings->mimesRule(), 'max:'.$this->settings->max_size_kb];
    }

    /**
     * Stocke une image « inline » (insérée dans un corps de texte riche) sur le
     * disque public et renvoie son URL. Indépendant d'un modèle (le HTML porte le src).
     */
    public function storeInlineImage(UploadedFile $file, string $disk = 'public'): string
    {
        $path = $file->store('editor/'.now()->format('Y/m'), $disk);

        return (string) Storage::disk($disk)->url((string) $path);
    }

    /**
     * Attache un fichier uploadé à une collection, avec texte alternatif.
     */
    public function attach(HasMedia $model, UploadedFile $file, string $collection, ?string $alt = null): Media
    {
        return $model->addMedia($file->getRealPath())
            ->usingFileName(Str::random(12).'.'.$file->getClientOriginalExtension())
            ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->withCustomProperties(array_filter(['alt' => $alt]))
            ->toMediaCollection($collection);
    }

    /**
     * Réordonne les médias d'une collection selon la liste d'IDs fournie.
     *
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        Media::setNewOrder(array_values(array_filter(array_map('intval', $orderedIds))));
    }

    public function setAlt(Media $media, ?string $alt): void
    {
        $media->setCustomProperty('alt', $alt ?? '');
        $media->save();
    }

    /**
     * Supprime les médias dont le modèle parent n'existe plus (fichiers orphelins).
     *
     * @return int nombre de médias purgés
     */
    public function pruneOrphans(): int
    {
        $pruned = 0;

        Media::query()->with('model')->chunkById(200, function ($chunk) use (&$pruned): void {
            foreach ($chunk as $media) {
                if ($media->model === null) {
                    $media->delete();
                    $pruned++;
                }
            }
        });

        return $pruned;
    }
}
