<?php
require 'config.php';

$db = db();

// Filters
$paciente_id = (int)($_GET['paciente_id'] ?? 0);
$busca       = trim($_GET['q'] ?? '');

if ($paciente_id) {
    $stmt = $db->prepare(
        "SELECT e.*, p.nome AS paciente_nome
         FROM exames e
         JOIN pacientes p ON p.id = e.paciente_id
         WHERE e.paciente_id = ?
         ORDER BY e.created_at DESC"
    );
    $stmt->execute([$paciente_id]);
} elseif ($busca) {
    $like = "%$busca%";
    $stmt = $db->prepare(
        "SELECT e.*, p.nome AS paciente_nome
         FROM exames e
         JOIN pacientes p ON p.id = e.paciente_id
         WHERE p.nome LIKE ? OR e.nome LIKE ? OR e.descricao LIKE ?
         ORDER BY e.created_at DESC"
    );
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->query(
        "SELECT e.*, p.nome AS paciente_nome
         FROM exames e
         JOIN pacientes p ON p.id = e.paciente_id
         ORDER BY e.created_at DESC
         LIMIT 100"
    );
}
$exames = $stmt->fetchAll();

// Lista de pacientes para o filtro
$pacientes = $db->query("SELECT id, nome FROM pacientes WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();

$pageTitle  = 'Exames';
$activePage = 'exames';
include 'includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
    <form method="get" action="exames.php" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <input type="text" name="q" value="<?= sanitize($busca) ?>" placeholder="Buscar por paciente, nome ou descrição…"
               style="padding:.5rem .75rem;border:1.5px solid #d1d5db;border-radius:6px;font-size:.875rem;min-width:260px;">
        <select name="paciente_id" class="form-control" style="min-width:200px;">
            <option value="">— Todos os pacientes —</option>
            <?php foreach ($pacientes as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $paciente_id === (int)$p['id'] ? 'selected' : '' ?>>
                <?= sanitize($p['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Buscar</button>
        <?php if ($paciente_id || $busca): ?>
        <a href="exames.php" class="btn btn-outline btn-sm">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($exames): ?>
<div class="card" style="padding:0;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>PACIENTE</th>
                <th>NOME DO EXAME</th>
                <th>DESCRIÇÃO</th>
                <th>ARQUIVO</th>
                <th>DATA</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($exames as $e): ?>
            <?php
            $isImagem = str_starts_with($e['arquivo_tipo'], 'image/');
            $isPdf    = $e['arquivo_tipo'] === 'application/pdf';
            $icone    = $isPdf ? 'PDF' : ($isImagem ? 'IMG' : 'ARQ');
            $tamanhoFmt = $e['arquivo_tamanho'] > 0
                ? (round($e['arquivo_tamanho'] / 1024, 1) < 1024
                    ? round($e['arquivo_tamanho'] / 1024, 1) . ' KB'
                    : round($e['arquivo_tamanho'] / (1024 * 1024), 1) . ' MB')
                : '';
            ?>
            <tr>
                <td>
                    <a href="paciente_ver.php?id=<?= $e['paciente_id'] ?>&tab=exames" style="color:#1a5fb4;font-weight:500;">
                        <?= sanitize($e['paciente_nome']) ?>
                    </a>
                </td>
                <td><?= sanitize($e['nome']) ?></td>
                <td style="color:#6b7280;font-size:.875rem;"><?= sanitize(mb_strimwidth($e['descricao'] ?? '', 0, 60, '…')) ?></td>
                <td>
                    <?= $icone ?> <span style="font-size:.82rem;color:#6b7280;"><?= sanitize($e['arquivo_nome']) ?><?= $tamanhoFmt ? " ({$tamanhoFmt})" : '' ?></span>
                </td>
                <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                <td style="white-space:nowrap;">
                    <a href="exame_arquivo.php?id=<?= $e['id'] ?>&acao=ver" target="_blank" class="btn btn-outline btn-sm">Ver</a>
                    <a href="exame_arquivo.php?id=<?= $e['id'] ?>&acao=baixar" class="btn btn-outline btn-sm">Baixar</a>
                    <form method="post" action="exame_excluir.php" style="display:inline;" onsubmit="return confirm('Excluir este exame?');">
                        <input type="hidden" name="exame_id" value="<?= $e['id'] ?>">
                        <input type="hidden" name="paciente_id" value="<?= $e['paciente_id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="card">
    <p style="color:#9ca3af;font-size:.9rem;">Nenhum exame encontrado.
        <?php if ($paciente_id): ?>
        <a href="paciente_ver.php?id=<?= $paciente_id ?>&tab=exames">Adicionar exame para este paciente</a>.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
