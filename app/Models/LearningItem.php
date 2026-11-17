<?php

namespace App\Models;

use App\Helpers\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A completed course (with its certificate image) or a learning plan, shown on the Education page.
 */
class LearningItem extends Model
{
    public const CERTIFICATE = 'certificate';
    public const PLAN = 'plan';

    protected $fillable = [
        'kind', 'title', 'issuer', 'completed_on', 'summary',
        'image', 'image_width', 'image_height', 'verification_url', 'sort',
    ];

    protected $casts = ['completed_on' => 'date:Y-m-d'];

    protected $appends = ['image_url'];

    public function scopeCertificates(Builder $q): Builder
    {
        return $q->where('kind', self::CERTIFICATE)->orderBy('completed_on')->orderBy('sort');
    }

    public function scopePlans(Builder $q): Builder
    {
        return $q->where('kind', self::PLAN)->orderBy('sort')->orderBy('id');
    }

    /** The images that shipped with the site sit in public/certificates/; uploads are on the media disk. */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return is_file(public_path($this->image)) ? asset($this->image) : MediaStorage::url($this->image);
    }
}
