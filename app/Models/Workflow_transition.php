<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow_transition extends Model {

    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'workflow_transitions';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['comment', 'prev_workflow_step_name', 'workflow_step_name', 'workflow_action_name', 'workflow_model_id', 'model_reference', 'created_user_id', 'created'];

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
    protected $dates = ['created'];

}