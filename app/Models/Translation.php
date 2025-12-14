<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    /**
     * Attributes that can be mass assigned.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'msgid',
        'msgstr',
        'context',
        'checksum',
    ];

    /**
     * Create a checksum that helps detect source changes.
     */
    public static function checksumFor(string $msgid, ?string $context = null): string
    {
        return hash('sha256', $context . '::' . $msgid);
    }
}
