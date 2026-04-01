<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EnterClinic — <?= $pageTitle ?? 'Dashboard' ?></title>
<link rel="stylesheet" href="<?= $root ?? '' ?>assets/css/style.css">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
    <img src="<?= $root ?? '' ?>assets/img/logo.png" alt="EnterClinic" class="logo-img">
</div>

    <nav class="sidebar-nav">
        <p class="nav-section">PRINCIPAL</p>
        <a href="<?= $root ?? '' ?>index.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>

        <p class="nav-section">PACIENTES</p>
        <a href="<?= $root ?? '' ?>pacientes.php" class="<?= ($activePage ?? '') === 'pacientes' ? 'active' : '' ?>">Pacientes</a>
        <a href="<?= $root ?? '' ?>paciente_novo.php" class="<?= ($activePage ?? '') === 'paciente_novo' ? 'active' : '' ?>">Novo Paciente</a>

        <p class="nav-section">CLÍNICO</p>
        <a href="<?= $root ?? '' ?>prontuarios.php" class="<?= ($activePage ?? '') === 'prontuarios' ? 'active' : '' ?>">Prontuários</a>
        <a href="<?= $root ?? '' ?>consultas.php" class="<?= ($activePage ?? '') === 'consultas' ? 'active' : '' ?>">Consultas</a>
        <a href="<?= $root ?? '' ?>exames.php" class="<?= ($activePage ?? '') === 'exames' ? 'active' : '' ?>">Exames</a>

        <p class="nav-section">SISTEMA</p>
        <a href="<?= $root ?? '' ?>configuracoes.php" class="<?= ($activePage ?? '') === 'configuracoes' ? 'active' : '' ?>">Configurações</a>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        <span class="topbar-date"><?= date('d/m/Y') ?> — Bem-vindo!</span>
    </header>
    <div class="content">
        <?php
        $flash = getFlash();
        if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['msg']) ?></div>
        <?php endif; ?>
