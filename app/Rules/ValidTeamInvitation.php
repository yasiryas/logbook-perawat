<?php

namespace App\Rules;

use App\Models\TeamInvitation;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidTeamInvitation implements ValidationRule
{
    public function __construct(protected ?User $user)
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof TeamInvitation || ! $this->user instanceof User) {
            $fail(__('Undangan ini dikirim ke alamat email yang berbeda.'));

            return;
        }

        if ($value->isAccepted()) {
            $fail(__('Undangan ini sudah diterima.'));

            return;
        }

        if ($value->isExpired()) {
            $fail(__('Undangan ini sudah kedaluwarsa.'));

            return;
        }

        if (strtolower($value->email) !== strtolower($this->user->email)) {
            $fail(__('Undangan ini dikirim ke alamat email yang berbeda.'));
        }
    }
}
