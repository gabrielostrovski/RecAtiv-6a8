<?php
session_start();

include 'funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validar_token_csrf()) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Não foi possível validar a solicitação.'];
    redirecionar_brinquedos();
}

$id_brinquedo = filter_var($_POST['brq_id_brinquedo'] ?? '', FILTER_VALIDATE_INT);

if ($id_brinquedo === false || $id_brinquedo < 1) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'O brinquedo selecionado é inválido.'];
    redirecionar_brinquedos();
}

try {
    include 'conexao.php';

    $stmt = $conexao->prepare('DELETE FROM brinquedos WHERE id_brinquedo = ?');
    $stmt->bind_param('i', $id_brinquedo);
    $stmt->execute();

    $_SESSION['mensagem'] = $stmt->affected_rows > 0
        ? ['tipo' => 'success', 'texto' => 'Brinquedo excluído com sucesso.']
        : ['tipo' => 'danger', 'texto' => 'Brinquedo não encontrado.'];
} catch (mysqli_sql_exception $erro) {
    error_log('Erro ao excluir brinquedo: ' . $erro->getMessage());
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Não foi possível excluir o brinquedo.'];
}

redirecionar_brinquedos();