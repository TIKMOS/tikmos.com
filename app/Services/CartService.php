<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Panier en session. Le prix et la disponibilité sont TOUJOURS recalculés côté serveur
 * (jamais de confiance au client). Survit à la connexion (la session est conservée).
 */
class CartService
{
    private const KEY = 'cart';

    private const MAX_QTY = 20;

    public function add(int $productId, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$productId] = min(self::MAX_QTY, ($cart[$productId] ?? 0) + max(1, $qty));
        $this->put($cart);
    }

    public function update(int $productId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min(self::MAX_QTY, $qty);
        }
        $this->put($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
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
     * @return Collection<int, array{product: Product, qty: int, line_total: int}>
     */
    public function items(): Collection
    {
        $cart = $this->raw();
        if ($cart === []) {
            return collect();
        }

        $products = Product::active()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = collect();
        $cleaned = [];
        foreach ($cart as $productId => $qty) {
            $product = $products->get($productId);
            if (! $product || $product->isOutOfStock()) {
                continue; // produit retiré du catalogue ou en rupture → purgé
            }
            $qty = (int) $qty;
            if ($product->stock !== null) {
                $qty = min($qty, $product->stock);
            }
            $cleaned[$productId] = $qty;
            $items->push([
                'product' => $product,
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

    /** @return array<int, int> */
    private function raw(): array
    {
        return (array) session(self::KEY, []);
    }

    /** @param  array<int, int>  $cart */
    private function put(array $cart): void
    {
        session()->put(self::KEY, $cart);
    }
}
