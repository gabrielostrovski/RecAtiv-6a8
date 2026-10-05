<?php
session_start();

include '../infra/funcoes.php';

$mensagem = $_SESSION['mensagem'] ?? null;
$erros = $_SESSION['erros_brinquedo'] ?? [];
$dados_formulario = $_SESSION['dados_brinquedo'] ?? [];
$id_edicao_formulario = $_SESSION['id_brinquedo_edicao'] ?? null;
unset(
    $_SESSION['mensagem'],
    $_SESSION['erros_brinquedo'],
    $_SESSION['dados_brinquedo'],
    $_SESSION['id_brinquedo_edicao']
);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$brinquedos = [];
$brinquedo_edicao = null;
$erro_banco = false;
$editar = filter_var($_GET['editar'] ?? '', FILTER_VALIDATE_INT);
$editar = $editar !== false && $editar > 0 ? $editar : null;

try {
    include '../infra/conexao.php';

    $stmt = $conexao->prepare(
        'SELECT id_brinquedo, nome, categoria, faixa_etaria, preco, quantidade
         FROM brinquedos
         ORDER BY nome'
    );
    $stmt->execute();
    $resultado = $stmt->get_result();
    $brinquedos = $resultado->fetch_all(MYSQLI_ASSOC);

    if ($editar !== null) {
        $stmt = $conexao->prepare(
            'SELECT id_brinquedo, nome, categoria, faixa_etaria, preco, quantidade
             FROM brinquedos
             WHERE id_brinquedo = ?'
        );
        $stmt->bind_param('i', $editar);
        $stmt->execute();
        $brinquedo_edicao = $stmt->get_result()->fetch_assoc();

        if ($brinquedo_edicao === null) {
            $mensagem = ['tipo' => 'danger', 'texto' => 'Brinquedo não encontrado.'];
            $editar = null;
        }
    }
} catch (mysqli_sql_exception $erro) {
    error_log('Erro ao carregar brinquedos: ' . $erro->getMessage());
    $erro_banco = true;
}

$formulario = $brinquedo_edicao ?? [
    'nome' => '',
    'categoria' => '',
    'faixa_etaria' => '',
    'preco' => '',
    'quantidade' => '',
];

if ($dados_formulario !== []) {
    $formulario = [
        'nome' => $dados_formulario['brq_nome'] ?? '',
        'categoria' => $dados_formulario['brq_categoria'] ?? '',
        'faixa_etaria' => $dados_formulario['brq_faixa_etaria'] ?? '',
        'preco' => $dados_formulario['brq_preco'] ?? '',
        'quantidade' => $dados_formulario['brq_quantidade'] ?? '',
    ];
    $editar = $id_edicao_formulario ?: null;
}

$total_brinquedos = count($brinquedos);
$total_unidades = array_sum(array_column($brinquedos, 'quantidade'));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brinquedos - Brinca Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4" href="../index.php">Brinca<span class="text-primary">Estoque</span></a>
            <span class="badge badge-soft rounded-pill px-3 py-2">Gestão da loja</span>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Brinquedos cadastrados</h1>
                <p class="text-secondary mb-0">Cadastro e controle de estoque da loja.</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge text-bg-light border px-3 py-2"><?= (int) $total_brinquedos ?> brinquedos</span>
                <span class="badge text-bg-light border px-3 py-2"><?= (int) $total_unidades ?> unidades</span>
            </div>
        </div>

        <?php if ($erro_banco) { ?>
            <div class="alert alert-danger" role="alert">
                Não foi possível carregar os brinquedos. Verifique o MySQL e importe
                <code>db/brinquedos.sql</code>.
            </div>
        <?php } ?>

        <?php if ($mensagem !== null) { ?>
            <div class="alert alert-<?= escapar($mensagem['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= escapar($mensagem['texto']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php } ?>

        <?php if ($erros !== []) { ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($erros as $erro) { ?>
                        <li><?= escapar($erro) ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <section class="card card-soft border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold text-uppercase text-secondary mb-3">
                            <?= $editar !== null ? 'Editar brinquedo' : 'Cadastrar brinquedo' ?>
                        </h2>
                        <form action="../infra/salvar_brinquedo.php" method="POST" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                            <?php if ($editar !== null) { ?>
                                <input type="hidden" name="brq_id_brinquedo" value="<?= (int) ($formulario['id_brinquedo'] ?? $editar) ?>">
                            <?php } ?>

                            <div class="mb-3">
                                <label for="brq_nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="brq_nome" name="brq_nome"
                                    maxlength="120" value="<?= escapar($formulario['nome']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="brq_categoria" class="form-label">Categoria</label>
                                <input type="text" class="form-control" id="brq_categoria" name="brq_categoria"
                                    maxlength="80" value="<?= escapar($formulario['categoria']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="brq_faixa_etaria" class="form-label">Faixa etária</label>
                                <input type="text" class="form-control" id="brq_faixa_etaria" name="brq_faixa_etaria"
                                    maxlength="50" placeholder="Ex.: 3 a 6 anos"
                                    value="<?= escapar($formulario['faixa_etaria']) ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="brq_preco" class="form-label">Preço (R$)</label>
                                    <input type="number" class="form-control" id="brq_preco" name="brq_preco"
                                        min="0" max="99999999.99" step="0.01"
                                        value="<?= escapar($formulario['preco']) ?>" required>
                                </div>
                                <div class="col-6">
                                    <label for="brq_quantidade" class="form-label">Estoque</label>
                                    <input type="number" class="form-control" id="brq_quantidade" name="brq_quantidade"
                                        min="0" step="1" value="<?= escapar($formulario['quantidade']) ?>" required>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?= $editar !== null ? 'Salvar alterações' : 'Cadastrar brinquedo' ?>
                                </button>
                                <?php if ($editar !== null) { ?>
                                    <a href="brinquedos.php" class="btn btn-outline-secondary">Cancelar</a>
                                <?php } ?>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-8">
                <section class="card card-soft border-0">
                    <div class="card-body p-0">
                        <div class="p-4 pb-3">
                            <h2 class="h5 fw-bold text-uppercase text-secondary mb-0">Listagem de brinquedos</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-soft">
                                <thead>
                                    <tr>
                                        <th scope="col">Nome</th>
                                        <th scope="col">Categoria</th>
                                        <th scope="col">Faixa etária</th>
                                        <th scope="col">Preço</th>
                                        <th scope="col">Estoque</th>
                                        <th scope="col">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($brinquedos === []) { ?>
                                        <tr>
                                            <td colspan="6" class="text-secondary text-center py-4">Nenhum brinquedo cadastrado ainda.</td>
                                        </tr>
                                    <?php } ?>
                                    <?php foreach ($brinquedos as $brinquedo) { ?>
                                        <tr>
                                            <td class="fw-semibold"><?= escapar($brinquedo['nome']) ?></td>
                                            <td><?= escapar($brinquedo['categoria']) ?></td>
                                            <td><?= escapar($brinquedo['faixa_etaria']) ?></td>
                                            <td>R$ <?= number_format((float) $brinquedo['preco'], 2, ',', '.') ?></td>
                                            <td>
                                                <span class="badge <?= (int) $brinquedo['quantidade'] === 0 ? 'text-bg-danger' : 'badge-soft' ?>">
                                                    <?= (int) $brinquedo['quantidade'] ?> un.
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <a href="?editar=<?= (int) $brinquedo['id_brinquedo'] ?>"
                                                    class="btn btn-outline-secondary btn-sm">Editar</a>
                                                <form class="d-inline" action="../infra/excluir_brinquedo.php" method="POST"
                                                    onsubmit="return window.confirm('Deseja excluir este brinquedo?');">
                                                    <input type="hidden" name="csrf_token" value="<?= escapar($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="brq_id_brinquedo" value="<?= (int) $brinquedo['id_brinquedo'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>