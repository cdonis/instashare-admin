<?php

namespace App\Models;

use App\Traits\Filtering;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory, Filtering;

    protected $fillable = ['name', 'md5', 'status', 'size', 'user_id'];

    public function getPath()
    {
        $basePath = ($this->status === 'ZIPPED') ? 'zipped' : 'plain';
        return "{$basePath}/{$this->md5}";
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
