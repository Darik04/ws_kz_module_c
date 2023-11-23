<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $appends = ['limit'];

    public function getLimitAttribute(){
        $quota = BillingQuota::all()->where('workspace', $this->id)->first();
        return $quota;
    }
}
