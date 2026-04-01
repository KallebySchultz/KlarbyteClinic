<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pacientes.php');
}

$db         = db();
$paciente_id = (int)($_POST['paciente_id'] ?? 0);
$nome        = trim($_POST['nome'] ?? '');
$descricao   = trim($_POST['descricao'] ?? '');

if (!$paciente_id || $nome === '') {
    flash('Preencha o nome do exame.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Verifica se o paciente existe
$stmt = $db->prepare("SELECT id FROM pacientes WHERE id = ?");
$stmt->execute([$paciente_id]);
if (!$stmt->fetch()) {
    flash('Paciente não encontrado.', 'error');
    redirect('pacientes.php');
}

// Valida o arquivo
if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    $erros = [
        UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o tamanho máximo definido no formulário.',
        UPLOAD_ERR_PARTIAL    => 'O arquivo foi carregado apenas parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco.',
        UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP interrompeu o envio.',
    ];
    $err = $erros[$_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Erro desconhecido no envio do arquivo.';
    flash($err, 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

$arquivo      = $_FILES['arquivo'];
$tamanho      = $arquivo['size'];
$nomeOriginal = basename($arquivo['name']);
$ext          = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

// Tipos permitidos
$tiposPermitidos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
if (!array_key_exists($ext, $tiposPermitidos)) {
    flash('Tipo de arquivo não permitido. Envie PDF, JPG, PNG ou GIF.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Valida MIME real do arquivo (não apenas a extensão)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeReal = $finfo->file($arquivo['tmp_name']);
$mimesValidos = array_unique(array_values($tiposPermitidos));
if (!in_array($mimeReal, $mimesValidos, true)) {
    flash('O conteúdo do arquivo não corresponde ao tipo permitido.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Limite de 20 MB
if ($tamanho > 20 * 1024 * 1024) {
    flash('O arquivo não pode ultrapassar 20 MB.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Gera nome único para o arquivo no disco
$nomeArquivo = bin2hex(random_bytes(16)) . '.' . $ext;
$destino     = __DIR__ . '/uploads/exames/' . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    flash('Falha ao salvar o arquivo. Verifique as permissões da pasta uploads/exames/.', 'error');
    redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
}

// Salva no banco
$usuarioId = $_SESSION['usuario_id'] ?? 1;
$stmt = $db->prepare(
    "INSERT INTO exames (paciente_id, usuario_id, nome, descricao, arquivo_path, arquivo_nome, arquivo_tipo, arquivo_tamanho)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$paciente_id, $usuarioId, $nome, $descricao ?: null, $nomeArquivo, $nomeOriginal, $mimeReal, $tamanho]);

flash('Exame adicionado com sucesso.');
redirect("paciente_ver.php?id={$paciente_id}&tab=exames");
