<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['company_id', 'documentable_type', 'documentable_id', 'title', 'file_path', 'document_type', 'uploaded_by'])]
class Document extends Model
{
    use BelongsToCompany, HasFactory;

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::saving(function (Document $document) {
            $parent = $document->documentable()->withoutGlobalScopes()->first();
            abort_unless($parent && (int) ($parent instanceof ManagementCompany ? $parent->id : $parent->company_id) === (int) $document->company_id, 403);
        });
    }
}
