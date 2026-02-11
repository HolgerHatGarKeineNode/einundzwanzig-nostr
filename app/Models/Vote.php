<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'einundzwanzig_pleb_id',
        'project_proposal_id',
        'value',
        'reason',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'einundzwanzig_pleb_id' => 'integer',
        'project_proposal_id' => 'integer',
        'value' => 'bool',
    ];

    public function einundzwanzigPleb(): BelongsTo
    {
        return $this->belongsTo(EinundzwanzigPleb::class);
    }

    public function projectProposal(): BelongsTo
    {
        return $this->belongsTo(ProjectProposal::class);
    }
}
