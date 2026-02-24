<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CmsContactSubmission extends Model
{
    use HasUuids;

    protected $table = 'cms_contact_submissions';

    protected $fillable = [
        'name',
        'email',
        'message',
        'submission_type',
        'product_id',
        'status',
    ];
}
