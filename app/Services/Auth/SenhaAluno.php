<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SenhaAluno
{
    public function confere(string $senha, ?string $hash): bool
    {
        if (! $hash) {
            return false;
        }
        // PHP reconhece $2b$ do Node em password_verify, mas alguns hashers Laravel o rejeitam.
        if (preg_match('/^\$2b\$[0-9]{2}\$[.\/A-Za-z0-9]{53}$/D', $hash)) {
            return password_verify($senha, $hash);
        }
        try {
            return Hash::check($senha, $hash);
        } catch (RuntimeException) {
            return false;
        }
    }
}
