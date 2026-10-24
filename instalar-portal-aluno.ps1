# Integra o design do Portal do Aluno ao CETSADP LMS.
# Por padrao, apenas confere. Use -Aplicar -IssueNumero <numero> para instalar.
# Executar a partir da raiz do projeto Laravel no terminal PowerShell do VS Code.
[CmdletBinding()]
param(
    [string]$ZipPath = (Join-Path $env:USERPROFILE 'Downloads\portal-aluno-novo-design (1).zip'),
    [string]$Projeto = (Get-Location).Path,
    [int]$IssueNumero = 0,
    [switch]$Aplicar
)
$ErrorActionPreference = 'Stop'
$prefixo = 'portal-aluno-novo-design/'
$raizPayload = $prefixo + 'aplicar-no-projeto/'

function Exigir-SucessoGit([string[]]$Argumentos) {
    # No Windows PowerShell, texto no stderr de um comando nativo pode virar
    # ErrorRecord e interromper a execucao sob ErrorActionPreference=Stop.
    # A falha real deve ser determinada pelo codigo de saida do Git.
    $preferenciaAnterior = $ErrorActionPreference
    $preferenciaNativaAnterior = $PSNativeCommandUseErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $PSNativeCommandUseErrorActionPreference = $false
        $resultado = & git @Argumentos 2>&1
        $codigoSaida = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $preferenciaAnterior
        $PSNativeCommandUseErrorActionPreference = $preferenciaNativaAnterior
    }
    if ($codigoSaida -ne 0) {
        throw ('Falha no Git: git ' + ($Argumentos -join ' ') + "`n" + ($resultado -join "`n"))
    }
    return $resultado
}

if (-not (Test-Path -LiteralPath $ZipPath -PathType Leaf)) {
    throw "ZIP nao encontrado: $ZipPath. Informe -ZipPath 'caminho-do-zip'."
}
$Projeto = (Resolve-Path -LiteralPath $Projeto).Path
foreach ($essencial in @('AGENTS.md', 'composer.json', 'artisan', 'resources/views', 'public')) {
    if (-not (Test-Path -LiteralPath (Join-Path $Projeto $essencial))) {
        throw "A pasta nao parece ser a raiz do CETSADP LMS (faltando $essencial): $Projeto"
    }
}
$originalDir = (Get-Location).Path
Set-Location -LiteralPath $Projeto
try {
    [void](Exigir-SucessoGit @('rev-parse', '--show-toplevel'))
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [IO.Compression.ZipFile]::OpenRead((Resolve-Path -LiteralPath $ZipPath).Path)
    try {
        $manifestEntry = $zip.GetEntry($prefixo + 'arquivos-alterados.json')
        if ($null -eq $manifestEntry) { throw 'Pacote invalido: arquivos-alterados.json ausente.' }
        $reader = New-Object IO.StreamReader($manifestEntry.Open(), [Text.Encoding]::UTF8)
        try { $manifesto = @((ConvertFrom-Json $reader.ReadToEnd())) }
        finally { $reader.Dispose() }
        $entradas = @{}
        foreach ($entrada in $zip.Entries) {
            if ($entrada.FullName.StartsWith($raizPayload) -and -not $entrada.FullName.EndsWith('/')) {
                $rel = $entrada.FullName.Substring($raizPayload.Length)
                if ($rel -match '(^|/|\\)\.\.($|/|\\)' -or $rel.StartsWith('/') -or $rel.Contains('\\')) {
                    throw "Caminho inseguro no ZIP: $rel"
                }
                if (-not ($rel.StartsWith('public/') -or $rel.StartsWith('resources/views/'))) {
                    throw "Arquivo inesperado no pacote de aplicacao: $rel"
                }
                if ($entradas.ContainsKey($rel)) { throw "Arquivo duplicado no ZIP: $rel" }
                $entradas[$rel] = $entrada
            }
        }
        if ($entradas.Count -ne $manifesto.Count -or $manifesto.Count -ne 67) {
            throw "Quantidade inesperada de arquivos: ZIP=$($entradas.Count), manifesto=$($manifesto.Count), esperado=67."
        }
        $sha256 = [Security.Cryptography.SHA256]::Create()
        try {
            $alterar = New-Object 'System.Collections.Generic.List[object]'
            $iguais = 0
            foreach ($item in $manifesto) {
                $rel = [string]$item.arquivo
                if (-not $entradas.ContainsKey($rel)) { throw "Manifesto sem entrada correspondente: $rel" }
                $stream = $entradas[$rel].Open()
                $buffer = New-Object IO.MemoryStream
                try { $stream.CopyTo($buffer) }
                finally { $stream.Dispose() }
                $bytes = $buffer.ToArray()
                $buffer.Dispose()
                $hashZip = ([BitConverter]::ToString($sha256.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant()
                if ($hashZip -ne [string]$item.sha256 -or $bytes.Length -ne [int]$item.bytes) {
                    throw "Integridade do ZIP nao confere: $rel"
                }
                $dest = Join-Path $Projeto ($rel.Replace('/', [IO.Path]::DirectorySeparatorChar))
                if ((Test-Path -LiteralPath $dest) -and -not (Test-Path -LiteralPath $dest -PathType Leaf)) {
                    throw "Destino nao e arquivo comum: $rel"
                }
                if (Test-Path -LiteralPath $dest -PathType Leaf) {
                    $hashAtual = (Get-FileHash -LiteralPath $dest -Algorithm SHA256).Hash.ToLowerInvariant()
                    if ($hashAtual -eq $hashZip) { $iguais++; continue }
                    $gitMudanca = @(& git status --porcelain --untracked-files=all -- $rel)
                    if ($LASTEXITCODE -ne 0) { throw "Falha consultando estado Git de $rel" }
                    if ($gitMudanca.Count -gt 0) {
                        throw "Conflito: $rel possui alteracoes locais. Resolva esse arquivo antes da instalacao."
                    }
                    $tipo = 'SUBSTITUIR'
                } else {
                    $tipo = 'NOVO'
                }
                $alterar.Add([pscustomobject]@{Caminho=$rel;Destino=$dest;Tipo=$tipo;Bytes=$bytes})
            }
        } finally { $sha256.Dispose() }

        $novos = @($alterar | Where-Object { $_.Tipo -eq 'NOVO' }).Count
        $substituir = @($alterar | Where-Object { $_.Tipo -eq 'SUBSTITUIR' }).Count
        $branchAtual = ((Exigir-SucessoGit @('branch', '--show-current')) | Out-String).Trim()
        Write-Host "Projeto: $Projeto"
        Write-Host "Branch atual: $branchAtual"
        Write-Host "ZIP verificado: $($manifesto.Count) arquivos validos (SHA-256)."
        Write-Host "Resultado: $novos novos; $substituir substituicoes; $iguais ja iguais."
        foreach ($a in $alterar) { Write-Host ('  ' + $a.Tipo.PadRight(12) + $a.Caminho) }
        if (-not $Aplicar) {
            Write-Host "`nSIMULACAO CONCLUIDA. Nenhum arquivo foi alterado."
            Write-Host 'Para instalar depois de abrir a Issue no GitHub:'
            Write-Host "  .\instalar-portal-aluno.ps1 -ZipPath '$ZipPath' -IssueNumero NUMERO -Aplicar"
            return
        }
        if ($IssueNumero -le 0) {
            throw 'Para aplicar, informe -IssueNumero com o numero da Issue de enhancement/feature no GitHub.'
        }
        $branchDestino = "feat/$IssueNumero-portal-aluno-novo-design"
        if ($branchAtual -ne $branchDestino) {
            $branchExiste = @(& git branch --list $branchDestino)
            if ($LASTEXITCODE -ne 0) { throw 'Nao foi possivel verificar a branch.' }
            if ($branchExiste.Count -gt 0) {
                throw "A branch $branchDestino ja existe. Entre nela manualmente e repita a instalacao."
            }
            [void](Exigir-SucessoGit @('switch', '-c', $branchDestino))
            Write-Host "Branch criada: $branchDestino"
        }
        # O backup fica na area local ignorada pelo Git, dentro do workspace.
        $backup = Join-Path $Projeto ('storage/app/portal-redesign-backups/portal-aluno-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
        [void](New-Item -ItemType Directory -Path $backup -Force)
        $criados = New-Object 'System.Collections.Generic.List[string]'
        $substituidos = New-Object 'System.Collections.Generic.List[object]'
        try {
            foreach ($a in $alterar) {
                if ($a.Tipo -eq 'SUBSTITUIR') {
                    $arquivoBackup = Join-Path $backup ($a.Caminho.Replace('/', [IO.Path]::DirectorySeparatorChar))
                    [void](New-Item -ItemType Directory -Path (Split-Path -Parent $arquivoBackup) -Force)
                    Copy-Item -LiteralPath $a.Destino -Destination $arquivoBackup
                    $substituidos.Add([pscustomobject]@{Destino=$a.Destino;Backup=$arquivoBackup})
                } else {
                    $criados.Add($a.Destino)
                }
                [void](New-Item -ItemType Directory -Path (Split-Path -Parent $a.Destino) -Force)
                [IO.File]::WriteAllBytes($a.Destino, $a.Bytes)
            }
        } catch {
            foreach ($f in $criados) { if (Test-Path -LiteralPath $f) { Remove-Item -LiteralPath $f -Force } }
            foreach ($s in $substituidos) { Copy-Item -LiteralPath $s.Backup -Destination $s.Destino -Force }
            throw "Falha ao copiar: os arquivos foram restaurados. Detalhe: $_"
        }
        Write-Host "`nArquivos aplicados. Backup de arquivos substituidos: $backup"
        if (Get-Command php -ErrorAction SilentlyContinue) {
            & php artisan view:clear
            $limpa = $LASTEXITCODE
            & php artisan view:cache
            $cache = $LASTEXITCODE
            if ($limpa -ne 0 -or $cache -ne 0) {
                Write-Warning 'Falha na atualizacao do cache Blade. Confira a configuracao do PHP/Laravel local.'
            }
        } else {
            Write-Warning 'PHP nao encontrado no PATH. Execute php artisan view:clear e php artisan view:cache no ambiente Laravel.'
        }
        Write-Host "`nEstado final do Git:"
        & git status --short --branch
        Write-Host "`nRevise e teste o portal autenticado antes de fazer commit, PR e deploy."
    } finally { $zip.Dispose() }
} finally { Set-Location -LiteralPath $originalDir }
