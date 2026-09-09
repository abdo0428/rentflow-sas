<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class Tenancy
{
    private bool $bypassed = false;

    private ?User $actor = null;

    public function user(): ?User
    {
        return $this->actor ?? Auth::user();
    }

    public function runFor(User $user, Closure $callback): mixed
    {
        $previous = $this->actor;
        $this->actor = $user;
        try {
            return $callback();
        } finally {
            $this->actor = $previous;
        }
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /** Explicit trusted context for seeders and background work; never from request input. */
    public function runWithoutScope(Closure $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;
        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }
}
