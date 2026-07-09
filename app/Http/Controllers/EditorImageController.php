<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Media\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Upload d'images « inline » depuis l'éditeur riche (contrat portable :
 * POST multipart `file` → 200 { url }). Réservé aux rôles de gestion (route admin).
 */
class EditorImageController extends Controller
{
    public function __invoke(Request $request, ImageService $images): JsonResponse
    {
        $request->validate([
            'file' => array_merge(['required'], $images->imageRules()),
        ]);

        return response()->json([
            'url' => $images->storeInlineImage($request->file('file')),
        ]);
    }
}
