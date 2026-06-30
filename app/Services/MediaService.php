<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gestion des images « autonomes » (non rattachées à un modèle) : réglages (hero…),
 * stockées sur le disque public et référencées par leur chemin dans les settings.
 * Les galeries rattachées à un modèle (produits, partenaires, équipe) passent par
 * spatie/laravel-medialibrary.
 */
class MediaService
{
    /** Extensions/MIME autorisés. */
    public const ALLOWED = ['image/png', 'image/jpeg', 'image/webp'];

    public const MAX_KB = 2048;

    /** Stocke une image et renvoie son chemin relatif (disque public). */
    public function storeImage(UploadedFile $file, string $dir = 'uploads'): string
    {
        return $file->store($dir, 'public');
    }

    /** Supprime une image existante (si présente). */
    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Remplace une image : supprime l'ancienne, stocke la nouvelle, renvoie le nouveau chemin. */
    public function replace(?string $oldPath, UploadedFile $file, string $dir = 'uploads'): string
    {
        $this->delete($oldPath);

        return $this->storeImage($file, $dir);
    }

    /** URL publique d'un chemin stocké. */
    public function url(?string $path): ?string
    {
        return ($path !== null && $path !== '') ? Storage::disk('public')->url($path) : null;
    }
}
