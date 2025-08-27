<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'title',
        'description',
        'url',
        'affiliate_url',
        'image',
        'original_price',
        'discounted_price',
        'discount_percentage',
        'store',
        'category',
        'status',
        'gemini_generated_post',
        'source_url',
        'posted_at',
        'is_approved'
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'posted_at' => 'datetime',
        'is_approved' => 'boolean'
    ];

    public function getFormattedOriginalPriceAttribute()
    {
        return 'R$ ' . number_format((float)$this->original_price, 2, ',', '.');
    }

    public function getFormattedDiscountedPriceAttribute()
    {
        return 'R$ ' . number_format((float)$this->discounted_price, 2, ',', '.');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }
}
