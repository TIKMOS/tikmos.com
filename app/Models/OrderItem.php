<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $unit_price
 * @property int $quantity
 * @property int $line_total
 */
#[Fillable(['order_id', 'product_id', 'name', 'size', 'unit_price', 'quantity', 'line_total'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotalLabel(): string
    {
        return number_format($this->line_total, 0, ',', ' ').' FCFA';
    }
}
