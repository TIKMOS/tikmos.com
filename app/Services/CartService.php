<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Panier en session. Le prix et la disponibilité sont TOUJOURS recalculés côté serveur
 * (jamais de confiance au client). Survit à la connexion (la session est conservée).
 *
 * Les lignes sont indexées par une clé « produit[:taille] » afin de gérer les pointures :
 * un même modèle en 41 et en 42 = deux lignes distinctes.
 */
class CartService
{
    private const KEY = 'cart';

    private const MAX_QTY = 20;

    public function add(int $productId, int $qty = 1, ?string $size = null): void
    {
        $key = $this->lineKey($productId, $size);
        $cart = $this->raw();
        $cart[$key] = min(self::MAX_QTY, ($cart[$key] ?? 0) + max(1, $qty));
        $this->put($cart);
    }

    public function update(string $key, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = min(self::MAX_QTY, $qty);
        }
        $this->put($cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        $this->put($cart);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Lignes du panier recalculées depuis la base (produits actifs uniquement).
     * Purge automatiquement les produits devenus indisponibles.
     *
     * @return Collection<int, array{key: string, product: Product, size: ?string, qty: int, line_total: int}>
     */
    public function items(): Collection
    {
        $cart = $this->raw();
        if ($cart === []) {
            return collect();
        }

        $ids = [];
        foreach (array_keys($cart) as $key) {
            [$id] = $this->parseKey((string) $key);
            $ids[$id] = true;
        }

        $products = Product::active()->whereIn('id', array_keys($ids))->get()->keyBy('id');

        $items = collect();
        $cleaned = [];
        foreach ($cart as $key => $qty) {
            [$productId, $size] = $this->parseKey((string) $key);
            $product = $products->get($productId);
            if (! $product || $product->isOutOfStock()) {
                continue; // produit retiré du catalogue ou en rupture → purgé
            }
            $qty = (int) $qty;
            if ($product->stock !== null) {
                $qty = min($qty, $product->stock);
            }
            $cleaned[(string) $key] = $qty;
            $items->push([
                'key' => (string) $key,
                'product' => $product,
                'size' => $size,
                'qty' => $qty,
                'line_total' => $product->price * $qty,
            ]);
        }

        if ($cleaned !== $cart) {
            $this->put($cleaned);
        }

        return $items;
    }

    public function count(): int
    {
        return (int) $this->items()->sum('qty');
    }

    public function subtotal(): int
    {
        return (int) $this->items()->sum('line_total');
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    private function lineKey(int $productId, ?string $size): string
    {
        $size = $size !== null ? trim($size) : '';

        return $size === '' ? (string) $productId : $productId.':'.$size;
    }

    /** @return array{0: int, 1: ?string} */
    private function parseKey(string $key): array
    {
        if (str_contains($key, ':')) {
            [$id, $size] = explode(':', $key, 2);

            return [(int) $id, $size === '' ? null : $size];
        }

        return [(int) $key, null];
    }

    /** @return array<string, int> */
    private function raw(): array
    {
        return (array) session(self::KEY, []);
    }

    /** @param  array<string, int>  $cart */
    private function put(array $cart): void
    {
        session()->put(self::KEY, $cart);
    }
}
