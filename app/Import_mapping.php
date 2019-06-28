<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Import_mapping extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'import_mapping';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['model', 'column_name', 'description', 'order', 'is_optional', 'foreign_key', 'lookup_plugin', 'lookup_model', 'lookup_column'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];


    public static  function getSheetColumns($model = null){
        $columns = Import_mapping::where('model', '=', $model)
            ->orderBy('order')
            ->get()->toArray();
        $slugs = [];
        foreach ($columns as $column) {

            $slugs[] = $column['slug'];
        }
        return $slugs;
    }
   

}