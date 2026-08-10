<?php
// ============================================
// HELPER FUNCTION - Para converter números em extenso
// Adicione em: app/Helpers/helpers.php
// ============================================

if (!function_exists('extenso')) {
    function extenso($valor) {
        $valor = number_format($valor, 2, '.', '');
        $partes = explode('.', $valor);
        $reais = (int) $partes[0];
        $centavos = (int) $partes[1];
        
        $resultado = numero_por_extenso($reais) . ' rea' . ($reais == 1 ? 'l' : 'is');
        
        if ($centavos > 0) {
            $resultado .= ' e ' . numero_por_extenso($centavos) . ' centavo' . ($centavos == 1 ? '' : 's');
        }
        
        return ucfirst($resultado);
    }
}

if (!function_exists('numero_por_extenso')) {
    function numero_por_extenso($numero) {
        $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
        $dez_a_dezenove = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
        
        if ($numero == 0) return 'zero';
        if ($numero == 100) return 'cem';
        
        $resultado = '';
        
        // Centenas
        if ($numero >= 100) {
            $resultado .= $centenas[floor($numero / 100)];
            $numero %= 100;
            if ($numero > 0) $resultado .= ' e ';
        }
        
        // 10-19
        if ($numero >= 10 && $numero <= 19) {
            $resultado .= $dez_a_dezenove[$numero - 10];
        }
        // Dezenas
        elseif ($numero >= 20) {
            $resultado .= $dezenas[floor($numero / 10)];
            $numero %= 10;
            if ($numero > 0) $resultado .= ' e ';
        }
        
        // Unidades
        if ($numero > 0 && $numero < 10) {
            $resultado .= $unidades[$numero];
        }
        
        return trim($resultado);
    }
}
