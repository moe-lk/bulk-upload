<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Unprocessed_students extends Model
{
    protected $table = 'unprocessed_students';
    protected $fillable = ['current_unprocessed_students_count', 'is_processed', 'notification', 'institution_id'];
    protected $hidden = [];
    protected $casts = [];
    protected $dates = [];
}
