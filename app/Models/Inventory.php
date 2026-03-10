<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $table = 'inventory';

    protected $fillable = [
        "type",
        "name",
        "vial_conc_mg",
        "vial_conc_ml",
        "inject_dosage",
        "iv_dosage",
        "inject_duration",
        "iv_duration",
        "ingredients",
        "sales_daily",
        "sales_monthly",
        "total_count",
        "level_min",
        "level_max",
        "others",
        "subitem",
        "price",
        "peptide",
        "deleted",
    ];
}
