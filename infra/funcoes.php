<?php

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function validar_brinquedo(array $dados)
{
    $brinquedo = [
        'nome' => trim((string) ($dados['brq_nome'] ?? '')),
        'categoria' => trim((string) ($dados['brq_categoria'] ?? '')),
        'faixa_etaria' => trim((string) ($dados['brq_faixa_etaria'] ?? '')),
        'preco' => trim(str_replace(',', '.', (string) ($dados['brq_preco'] ?? ''))),
        'quantidade' => trim((string) ($dados['brq_quantidade'] ?? '')),
    ];
    $erros = [];

    if ($brinquedo['nome'] === '' || strlen($brinquedo['nome']) > 120) {
        $erros[] = 'Informe um nome com até 120 caracteres.';
    }
    if ($brinquedo['categoria'] === '' || strlen($brinquedo['categoria']) > 80) {
        $erros[] = 'Informe uma categoria com até 80 caracteres.';
    }
    if ($brinquedo['faixa_etaria'] === '' || strlen($brinquedo['faixa_etaria']) > 50) {
        $erros[] = 'Informe uma faixa etária com até 50 caracteres.';
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $brinquedo['preco'])) {
        $erros[] = 'Informe um preço válido, com até duas casas decimais.';
    }
    if (filter_var($brinquedo['quantidade'], FILTER_VALIDATE_INT) === false
        || (int) $brinquedo['quantidade'] < 0) {
        $erros[] = 'A quantidade deve ser um número inteiro igual ou maior que zero.';
    }

    return [$brinquedo, $erros];
}

function redirecionar_brinquedos($parametros = '')
{
    header('Location: ../public/brinquedos.php' . $parametros);
    exit;
}

function validar_token_csrf()
{
    return isset($_SESSION['csrf_token'], $_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}