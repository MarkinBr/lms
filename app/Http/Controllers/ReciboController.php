<?php

namespace App\Http\Controllers;

use App\Models\Mensalidade;
use Illuminate\Http\Request;

class ReciboController extends Controller
{
    public function show($id)
    {
        $mensalidade = Mensalidade::findOrFail($id);

        // Só mostra recibo se estiver pago
        if ($mensalidade->status !== 'Pago') {
            abort(403, 'Esta mensalidade ainda não foi paga.');
        }

        return view('recibo', compact('mensalidade'));
    }
}