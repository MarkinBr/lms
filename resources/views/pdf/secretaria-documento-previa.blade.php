<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Prévia - {{ \App\Models\SecretariaDocumento::TIPOS[$documento->tipo] ?? 'Documento acadêmico' }}</title>
<style>
@page { size: A4 portrait; margin: 11mm 11mm 11mm 11mm; }
* { box-sizing: border-box; }
body { margin:0; font-family:DejaVu Sans,sans-serif; font-size:11px; color:#14243a; }
.frame { position:relative; border:2px solid #173b71; min-height:266mm; padding:17mm 14mm 12mm; background:#fff; }
.model-classico .frame { border:3px double #143975; }
.model-mosaico .frame { border:2px solid #0a3578; padding-top:21mm; }
.model-solene .frame { border:3px solid #c39133; outline:1px solid #092c62; outline-offset:-6px; padding-top:23mm; }
.model-minimalista .frame { border:1px solid #abb5c0; padding-top:21mm; }
.brand { width:100%; border-collapse:collapse; border-bottom:1px solid #cbd5e0; padding-bottom:10px; }
.brand td { vertical-align:middle; }
.logo-cets { height:58px; max-width:240px; }
.logo-ieadp { height:66px; max-width:90px; }
.brand-center { text-align:center; width:50%; }
.brand-center .eyebrow { color:#a67f41; font-size:8px; letter-spacing:1.2px; }
.brand-center strong { font-size:13px; color:#0a3267; display:block; margin:5px 0; }
.brand-center small { font-size:8px; color:#586d80; }
.title { margin:28px 0 24px;text-align:center;font-weight:bold;font-size:22px;letter-spacing:0.9px;color:#0a3267; }
.model-solene .title { color:white;background:#082e67;padding:15px 12px;border-top:4px solid #d6ad5b;border-bottom:4px solid #d6ad5b; }
.model-mosaico .title { border-bottom:2px solid #b93237;padding-bottom:11px; }
.model-minimalista .title { font-family:DejaVu Serif,serif;color:#202d46;border-bottom:1px solid #8b99a6;padding-bottom:12px; }
.topic {width:100%;border-collapse:separate;border-spacing:0 6px;margin-bottom:20px;}
.topic td {padding:6px 8px;vertical-align:top;font-size:11px;}
.topic .k {width:33%;color:#0a2e62;font-weight:bold;}
.model-solene .topic td {background:#e8f0f7;}
.model-solene .topic .k {border-left:3px solid #c49339;}
.model-mosaico .topic .k {border-left:2px solid #0a387c;}
.model-minimalista .topic td {border-bottom:1px dotted #c9d0d9;}
.separator {border-top:1px solid #b68c4c;margin:20px 0 24px;text-align:center;height:1px;}
.separator img {height:34px;position:relative;top:-15px;background:white;padding:0 10px;}
.paragraph {font-size:12px;line-height:1.85;text-align:justify;margin:0 0 16px;}
.paragraph strong {color:#0a2e62;}
.notice {border-left:4px solid #b78d4d;background:#f2f5f8;padding:12px 13px;font-size:10px;color:#546579;margin-top:18px;}
.issue-date {font-size:11px;margin-top:24px;text-align:right;}
.sign {margin:27px auto 23px;text-align:center;width:76%;}
.sign img {height:48px;max-width:240px;}
.sign .line {border-top:1px solid #173b71;margin-top:8px;padding-top:7px;}
.sign strong {font-size:10px;color:#092e61;}
.sign small {font-size:9px;color:#647483;}
.validation {border:1px solid #91add0;background:#f7f9fb;padding:10px 12px;margin-top:23px;width:100%;border-collapse:collapse;}
.validation td {vertical-align:middle;}
.validation .titlebox {border-left:5px solid #b98939;padding:0 11px;}
.validation .titlebox strong {display:block;color:#0b3061;font-size:11px;}
.validation .titlebox small {display:block;color:#6e4e22;margin-top:3px;}
.validation .placeholder {font-size:8px;width:89px;text-align:center;color:#6a7785;border-left:1px solid #a9c1da;}
.validation .placeholder .qr {border:1px dashed #9eaabd;padding:15px 8px;margin:3px 4px;font-weight:bold;}
.ribbon {text-align:center;margin-top:13px;font-size:8px;font-weight:bold;letter-spacing:.8px;background:#0b3269;color:white;padding:8px;}
.model-solene .ribbon {border-top:2px solid #c69744;border-bottom:2px solid #c69744;}
.model-minimalista .ribbon {background:#eef0f4;color:#48586b;}
.mark {position:fixed;left:62mm;top:101mm;opacity:.05;z-index:-1;}
.mark img {width:95mm;height:auto;}
.model-solene .mark {opacity:.065;}
.model-mosaico .stripe {position:absolute;top:5mm;left:5mm;right:5mm;height:9px;background:#143d7c;}
.model-mosaico .squares {margin:0 2mm;text-align:center;position:absolute;top:1.5mm;left:0;right:0;font-size:7px;letter-spacing:3px;color:#0b3a82;}
.model-classico .ornament,.model-mosaico .ornament {text-align:center;}
.ornament {position:absolute;left:6mm;right:6mm;bottom:4mm;text-align:center;font-size:8px;color:#93a8bd;}
.ornament img {height:15px;max-width:55px;vertical-align:middle;margin:0 15px;}
.model-minimalista .ornament {color:#8c969e;}
.side-symbol {position:absolute;top:109mm;height:40px;opacity:.12;}
.side-symbol.left {left:1.5mm;}.side-symbol.right{right:1.5mm;}
.model-solene .side-symbol {opacity:.08;}
</style>
</head>
@php
    $titulo = \App\Models\SecretariaDocumento::TIPOS[$documento->tipo] ?? 'Documento acadêmico';
    $nome = (string)($dados['estudante'] ?? 'Estudante');
    $numero = (string)($dados['numero_matricula'] ?? 'S/N');
    $curso = (string)($dados['curso'] ?? 'Curso não informado');
    $situacao = (string)($dados['situacao'] ?? 'Não informada');
    $tipoComprovante = $documento->tipo === 'comprovante_matricula';
@endphp
<body class="model-{{ $documento->modelo }}">
    <div class="frame">
        <div class="stripe"></div><div class="squares">■ ▫ ■ ▫ ■ ▫ ■ ▫ ■ ▫ ■ ▫ ■</div>
        <div class="mark">@if($logos['cetsadp'])<img src="{{ $logos['cetsadp'] }}" alt="">@endif</div>
        @if($logos['ieadp'])<img class="side-symbol left" src="{{ $logos['ieadp'] }}" alt="">@endif
        @if($logos['cetsadp'])<img class="side-symbol right" src="{{ $logos['cetsadp'] }}" alt="">@endif
        <table class="brand"><tr>
            <td style="width:40%">@if($logos['cetsadp'])<img class="logo-cets" src="{{ $logos['cetsadp'] }}" alt="CETSADP">@else<strong>CETSADP</strong>@endif</td>
            <td class="brand-center"><span class="eyebrow">DOCUMENTAÇÃO ACADÊMICA</span><strong>CETSADP · IEADP</strong><small>Centro Educacional, Teológico e Social<br>Assembleia de Deus de Paracambi</small></td>
            <td style="width:24%;text-align:right">@if($logos['ieadp'])<img class="logo-ieadp" src="{{ $logos['ieadp'] }}" alt="IEADP">@else<strong>IEADP</strong>@endif</td>
        </tr></table>
        <h1 class="title">{{ mb_strtoupper($titulo) }}</h1>
        <table class="topic">
            <tr><td class="k">Estudante</td><td>{{ $nome }}</td></tr>
            <tr><td class="k">Matrícula acadêmica</td><td>{{ $numero }}</td></tr>
            <tr><td class="k">Curso</td><td>{{ $curso }}</td></tr>
            <tr><td class="k">Turma</td><td>{{ $dados['turma']??'Não informada' }}</td></tr>
            <tr><td class="k">Situação acadêmica</td><td>{{ $situacao }}</td></tr>
            <tr><td class="k">Finalidade informada</td><td>{{ $documento->finalidade }}</td></tr>
        </table>
        <div class="separator">@if($logos['cetsadp'])<img src="{{ $logos['cetsadp'] }}" alt="">@endif</div>
        <p class="paragraph">
            @if($tipoComprovante)
                Consta no cadastro acadêmico do CETSADP que <strong>{{ $nome }}</strong>
                possui registro de matrícula nº <strong>{{ $numero }}</strong>, no curso
                <strong>{{ $curso }}</strong>, com situação <strong>{{ $situacao }}</strong>.
                Este comprovante não afirma frequência, conclusão ou aprovação em disciplinas.
            @else
                Declaramos, exclusivamente para conferência interna, que <strong>{{ $nome }}</strong>
                possui registro de matrícula nº <strong>{{ $numero }}</strong>, no curso
                <strong>{{ $curso }}</strong>, com situação acadêmica <strong>{{ $situacao }}</strong>.
            @endif
        </p>
        <p class="paragraph"><strong>Finalidade:</strong> {{ $documento->finalidade }}.</p>
        <div class="notice">PRÉVIA PARA CONFERÊNCIA — documento não emitido. Os dados e a assinatura gráfica não constituem certificação digital. Não utilize como declaração oficial.</div>
        <div class="issue-date">Paracambi (RJ), {{ now()->format('d/m/Y') }}.</div>
        <div class="sign">
            @if($assinaturaGrafica)<img src="{{ $assinaturaGrafica }}" alt="Assinatura gráfica">@endif
            <div class="line"><strong>{{ $documento->assinatura?->responsavel?->name??'Secretaria Acadêmica · CETSADP' }}</strong><br>
                <small>{{ $documento->assinatura?->cargo??'Campo reservado à assinatura' }} · prévia não certificada</small></div>
        </div>
        <table class="validation"><tr><td class="titlebox">
            <strong>DOCUMENTO DIGITAL · PRÉVIA INTERNA</strong>
            <small>REGISTRO {{ str_pad((string)$documento->id,6,'0',STR_PAD_LEFT) }} · {{ strtoupper(str_replace('_',' ',$documento->status)) }}</small>
            <p style="margin:6px 0 0;font-size:9px;color:#394f63">Sem emissão, QR de validação ou autenticidade oficial nesta versão.</p>
        </td><td class="placeholder"><div class="qr">QR<br>FUTURO</div>Não verificável</td></tr></table>
        <div class="ribbon">PROTÓTIPO APROVADO COM RESSALVAS · SEM VALIDADE DOCUMENTAL</div>
        <div class="ornament">
            @if($logos['cetsadp'])<img src="{{ $logos['cetsadp'] }}" alt="">@endif
            @if($logos['ieadp'])<img src="{{ $logos['ieadp'] }}" alt="">@endif
            SIMBOLOGIA ORIGINAL CETSADP / IEADP
        </div>
    </div>
</body>
</html>
