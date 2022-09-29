<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apinotas extends Model
{
    use HasFactory;
    protected $table = 'apinotas';
    protected $fillable = ['consecutivo'];
}
