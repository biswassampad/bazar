<?php

namespace Bazar\Models;

use Bazar\Bazar;
use Bazar\Casts\Driver;
use Bazar\Concerns\Addressable;
use Bazar\Concerns\InteractsWithTaxes;
use Bazar\Contracts\Taxable;
use Bazar\Support\Facades\Shipping as Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Throwable;

class Shipping extends Model implements Taxable
{
    use Addressable, InteractsWithTaxes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'driver_name',
        'formatted_total',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'tax' => 0,
        'cost' => 0,
        'driver' => 'local-pickup',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'driver' => Driver::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tax',
        'cost',
        'driver',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleting(function (Shipping $shipping) {
            $shipping->address()->delete();
        });
    }

    /**
     * Get the shippable model for the shipping.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function shippable(): MorphTo
    {
        return $this->morphTo()->withDefault([
            'currency' => Bazar::currency(),
        ]);
    }

    /**
     * Get the total attribute.
     *
     * @return float
     */
    public function getTotalAttribute(): float
    {
        return $this->total();
    }

    /**
     * Get the formatted total attribute.
     *
     * @return string
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->formattedTotal();
    }

    /**
     * Get the net total attribute.
     *
     * @return float
     */
    public function getNetTotalAttribute(): float
    {
        return $this->netTotal();
    }

    /**
     * Get the formatted net total attribute.
     *
     * @return string
     */
    public function getFormattedNetTotalAttribute(): string
    {
        return $this->formattedNetTotal();
    }

    /**
     * Get the quantity attribute.
     *
     * @return int
     */
    public function getQuantityAttribute(): int
    {
        return 1;
    }

    /**
     * Get the price attribute.
     *
     * @return float
     */
    public function getPriceAttribute(): float
    {
        return $this->cost;
    }

    /**
     * Get the name of the shipping method.
     *
     * @return string
     */
    public function getDriverNameAttribute(): string
    {
        try {
            return Manager::driver($this->driver)->name();
        } catch (Throwable $e) {
            return $this->driver;
        }
    }

    /**
     * Get the shipping's total.
     *
     * @return float
     */
    public function total(): float
    {
        return $this->cost + $this->tax;
    }

    /**
     * Get the shipping's formatted total.
     *
     * @return string
     */
    public function formattedTotal(): string
    {
        return Str::currency($this->total(), $this->shippable->currency);
    }

    /**
     * Get the shipping's net total.
     *
     * @return float
     */
    public function netTotal(): float
    {
        return $this->cost;
    }

    /**
     * Get the shipping's formatted net total.
     *
     * @return string
     */
    public function formattedNetTotal(): string
    {
        return Str::currency($this->netTotal(), $this->shippable->currency);
    }

    /**
     * Set the driver.
     *
     * @param  string  $driver
     * @return $this
     */
    public function driver(string $driver): Shipping
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Calculate the cost.
     *
     * @param  bool  $update
     * @return float
     */
    public function cost(bool $update = true): float
    {
        try {
            $this->cost = Manager::driver($this->driver)->calculate($this->shippable);

            if ($this->exists && $update) {
                $this->update(['cost' => $this->cost]);
            }
        } catch (Throwable $e) {
            //
        }

        return $this->cost;
    }
}