<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'template',
        'price',
        'payment_gateway',
        'video_path',
        'cover_images',
        'is_active',
        'pesalink_account_id',
        'mobilipa_account_id',
        'sonicpesa_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'cover_images' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function pesalinkAccount()
    {
        return $this->belongsTo(PesaLinkAccount::class, 'pesalink_account_id');
    }

    public function mobilipaAccount()
    {
        return $this->belongsTo(MobilipaAccount::class, 'mobilipa_account_id');
    }

    public function sonicpesaAccount()
    {
        return $this->belongsTo(SonicPesaAccount::class, 'sonicpesa_account_id');
    }
}
