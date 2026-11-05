<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'title_ms', 'body_ms', 'event_date', 'created_by'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Title in the selected language (falls back to the other one). */
    public function localTitle(): string
    {
        return app()->getLocale() === 'ms' && $this->title_ms ? $this->title_ms : $this->title;
    }

    public function localBody(): string
    {
        return app()->getLocale() === 'ms' && $this->body_ms ? $this->body_ms : $this->body;
    }
}
