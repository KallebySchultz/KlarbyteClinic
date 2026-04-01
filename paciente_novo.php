<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$db  = db();
$id  = (int)($_GET['id'] ?? 0);
$editing = $id > 0;


$paciente  = [];
$anamnese  = [];
$campos    = $db->query("SELECT * FROM campos_anamnese WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll();
$validAnamneseCols = $db->query("SHOW COLUMNS FROM anamnese")->fetchAll(PDO::FETCH_COLUMN);
$usuarioId = $_SESSION['usuario_id'] ?? 1;

if ($editing) {
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id]);
    $paciente = $stmt->fetch();
    if (!$paciente) { flash('Paciente não encontrado.', 'error'); redirect('pacientes.php'); }

    $stmt2 = $db->prepare("SELECT * FROM anamnese WHERE paciente_id = ? ORDER BY id DESC LIMIT 1");
    $stmt2->execute([$id]);
    $anamnese = $stmt2->fetch() ?: [];
}

$pageTitle  = $editing ? 'Editar Paciente' : 'Novo Paciente';
$activePage = $editing ? 'pacientes' : 'paciente_novo';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome         = trim($_POST['nome'] ?? '');
    $dataNasc     = $_POST['data_nascimento'] ?? '';
    $sexo         = $_POST['sexo'] ?? 'M';
    $cpf          = trim($_POST['cpf'] ?? '');
    $celular      = trim($_POST['celular'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $endereco     = trim($_POST['endereco'] ?? '');
    $cidade       = trim($_POST['cidade'] ?? '');
    $profissao    = trim($_POST['profissao'] ?? '');
    $estadoCivil  = trim($_POST['estado_civil'] ?? '');
    $numeroFilhos = $_POST['numero_filhos'] !== '' ? (int)$_POST['numero_filhos'] : null;
    $observacoes  = trim($_POST['observacoes'] ?? '');

    if (!$nome) {
        flash('O nome do paciente é obrigatório.', 'error');
    } else {

        try {
            $db->beginTransaction();

            if ($editing) {
                $stmt = $db->prepare(
                    "UPDATE pacientes SET nome=?, data_nascimento=?, sexo=?, cpf=?, celular=?, email=?,
                     endereco=?, cidade=?, profissao=?, estado_civil=?, numero_filhos=?, observacoes=? WHERE id=?"
                );
                $stmt->execute([$nome, $dataNasc ?: null, $sexo, $cpf, $celular, $email,
                                $endereco, $cidade, $profissao, $estadoCivil, $numeroFilhos, $observacoes, $id]);
                $pacienteId = $id;
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO pacientes (nome, data_nascimento, sexo, cpf, celular, email,
                     endereco, cidade, profissao, estado_civil, numero_filhos, observacoes)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([$nome, $dataNasc ?: null, $sexo, $cpf, $celular, $email,
                                $endereco, $cidade, $profissao, $estadoCivil, $numeroFilhos, $observacoes]);
                $pacienteId = $db->lastInsertId();
            }

            // =========================
            // ✅ ANAMNESE DINÂMICA
            // =========================
            $anamneseData = [];

            // Only include campos whose nome matches an actual column in the anamnese table
            foreach ($campos as $campo) {
                if (in_array($campo['nome'], $validAnamneseCols)) {
                    $anamneseData[$campo['nome']] = trim($_POST['anamnese_' . $campo['nome']] ?? '');
                }
            }

            // Verifica se já existe
            $existsStmt = $db->prepare("SELECT id FROM anamnese WHERE paciente_id = ?");
            $existsStmt->execute([$pacienteId]);
            $existingAnamnese = $existsStmt->fetch();

            $cols = array_keys($anamneseData);

            if ($existingAnamnese) {
                // UPDATE dinâmico + usuario_id
                $sets = "usuario_id = ?, " . implode(', ', array_map(fn($c) => "$c = ?", $cols));

                $vals = array_merge(
                    [$usuarioId],
                    array_values($anamneseData),
                    [$pacienteId]
                );

                $stmt = $db->prepare("UPDATE anamnese SET $sets WHERE paciente_id = ?");
                $stmt->execute($vals);
            } else {
                // INSERT dinâmico + usuario_id
                $colStr = implode(', ', $cols);
                $phStr  = implode(', ', array_fill(0, count($cols), '?'));

                $stmt = $db->prepare("INSERT INTO anamnese (usuario_id, paciente_id, $colStr) VALUES (?, ?, $phStr)");

                $stmt->execute(array_merge(
                    [$usuarioId, $pacienteId],
                    array_values($anamneseData)
                ));
            }

            $db->commit();

            flash($editing ? 'Paciente atualizado com sucesso.' : 'Paciente cadastrado com sucesso.');
            redirect('paciente_ver.php?id=' . $pacienteId);

        } catch (Exception $e) {
            $db->rollBack();
            die("Erro ao salvar: " . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div class="page-bar">
    <h2><?= $editing ? 'Editar Paciente' : 'Novo Paciente' ?></h2>
    <?php if ($editing): ?>
        <a href="paciente_ver.php?id=<?= $id ?>" class="btn btn-outline">← Voltar</a>
    <?php else: ?>
        <a href="pacientes.php" class="btn btn-outline">← Pacientes</a>
    <?php endif; ?>
</div>

<form method="post">
    <div class="card">
        <h2>Dados Pessoais</h2>
        <div class="form-grid">
            <div class="form-group col-span-2">
                <label>Nome Completo *</label>
                <input type="text" name="nome" required value="<?= sanitize($paciente['nome'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" value="<?= $paciente['data_nascimento'] ?? '' ?>">
            </div>

            <div class="form-group">
                <label>Sexo</label>
                <select name="sexo">
                    <option value="M" <?= ($paciente['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= ($paciente['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                    <option value="O" <?= ($paciente['sexo'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div class="form-group">
                <label>CPF</label>
                <input type="text" name="cpf" value="<?= sanitize($paciente['cpf'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Celular</label>
                <input type="text" name="celular" value="<?= sanitize($paciente['celular'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= sanitize($paciente['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Profissão</label>
                <input type="text" name="profissao" value="<?= sanitize($paciente['profissao'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Estado Civil</label>
                <select name="estado_civil">
                    <?php foreach (['','Solteiro(a)','Casado(a)','Divorciado(a)','Viúvo(a)','União Estável'] as $ec): ?>
                        <option value="<?= $ec ?>" <?= ($paciente['estado_civil'] ?? '') === $ec ? 'selected' : '' ?>>
                            <?= $ec ?: 'Selecione…' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Número de Filhos</label>
                <input type="number" name="numero_filhos" min="0" value="<?= $paciente['numero_filhos'] ?? '' ?>">
            </div>

            <div class="form-group col-span-3">
                <label>Endereço</label>
                <input type="text" name="endereco" value="<?= sanitize($paciente['endereco'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Cidade</label>
                <input type="text" name="cidade" value="<?= sanitize($paciente['cidade'] ?? '') ?>">
            </div>

            <div class="form-group col-span-2">
                <label>Observações</label>
                <textarea name="observacoes"><?= sanitize($paciente['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Anamnese</h2>

        <?php if (empty($campos)): ?>
            <p>Nenhum campo configurado.</p>
        <?php else: ?>
            <div class="form-grid form-grid-1">
                <?php foreach ($campos as $campo): ?>
                    <div class="form-group">
                        <label><?= sanitize($campo['label']) ?></label>

                        <?php $val = sanitize($anamnese[$campo['nome']] ?? ''); ?>

                        <?php if ($campo['tipo'] === 'textarea'): ?>
                            <textarea name="anamnese_<?= $campo['nome'] ?>"><?= $val ?></textarea>

                        <?php elseif ($campo['tipo'] === 'select' && $campo['opcoes']): ?>
                            <select name="anamnese_<?= $campo['nome'] ?>">
                                <option value="">Selecione…</option>
                                <?php foreach (explode("\n", $campo['opcoes']) as $opt): ?>
                                    <?php $opt = trim($opt); if (!$opt) continue; ?>
                                    <option value="<?= sanitize($opt) ?>" <?= ($anamnese[$campo['nome']] ?? '') === $opt ? 'selected' : '' ?>>
                                        <?= sanitize($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php else: ?>
                            <input type="text" name="anamnese_<?= $campo['nome'] ?>" value="<?= $val ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="<?= $editing ? 'paciente_ver.php?id='.$id : 'pacientes.php' ?>" class="btn btn-outline">Cancelar</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>