<?php

namespace RajuBepary\LaravelModule\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property bool $is_active
 * @property string $slug
 * @property string $name
 * @property string $version
 */
class Module extends Model
{
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('laravel-module.table_name', parent::getTable());
    }

    /**
     * Attributes mass-assignable through create/update.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'version',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
