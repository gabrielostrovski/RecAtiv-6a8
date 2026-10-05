<?php
session_start();

include 'funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validar_token_csrf()) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Não foi possível validar a solicitação.'];
    redirecionar_brinquedos();
}

[$brinquedo, $erros] = validar_brinquedo($_POST);
$id_brinquedo = filter_var($_POST['brq_id_brinquedo'] ?? '', FILTER_VALIDATE_INT);
$edicao = $id_brinquedo !== false && $id_brinquedo > 0;

if (isset($_POST['brq_id_brinquedo']) && $_POST['brq_id_brinquedo'] !== '' && !$edicao) {
    $erros[] = 'O brinquedo selecionado é inválido.';
}

if ($erros !== []) {
    $_SESSION['erros_brinquedo'] = $erros;
    $_SESSION['dados_brinquedo'] = $_POST;
    $_SESSION['id_brinquedo_edicao'] = $edicao ? $id_brinquedo : null;
    redirecionar_brinquedos($edicao ? '?editar=' . $id_brinquedo : '');
}

try {
    include 'conexao.php';

    if ($edicao) {
        $sql = 'UPDATE brinquedos
                SET nome = ?, categoria = ?, faixa_etaria = ?, preco = ?, quantidade = ?
                WHERE id_brinquedo = ?';
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param(
            'ssssii',
            $brinquedo['nome'],
            $brinquedo['categoria'],
            $brinquedo['faixa_etaria'],
            $brinquedo['preco'],
            $brinquedo['quantidade'],
            $id_brinquedo
        );
        $stmt->execute();
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Brinquedo atualizado com sucesso.'];
    } else {
        $sql = 'INSERT INTO brinquedos (nome, categoria, faixa_etaria, preco, quantidade)
                VALUES (?, ?, ?, ?, ?)';
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param(
            'ssssi',
            $brinquedo['nome'],
            $brinquedo['categoria'],
            $brinquedo['faixa_etaria'],
            $brinquedo['preco'],
            $brinquedo['quantidade']
        );
        $stmt->execute();
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Brinquedo cadastrado com sucesso.'];
    }
} catch (mysqli_sql_exception $erro) {
    error_log('Erro ao salvar brinquedo: ' . $erro->getMessage());
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Não foi possível salvar o brinquedo.'];
}

redirecionar_brinquedos();