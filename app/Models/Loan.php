<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = ['borrower_name', 'phone_number', 'due_date', 'idempotency_key'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}
