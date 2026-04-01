-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01-Abr-2026 às 21:26
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `enterclinic`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `anamnese`
--

CREATE TABLE `anamnese` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `queixa_principal` text DEFAULT NULL,
  `historia_doenca` text DEFAULT NULL,
  `antecedentes_pessoais` text DEFAULT NULL,
  `antecedentes_familiares` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `habitos` text DEFAULT NULL,
  `alimentacao` text DEFAULT NULL,
  `sono` text DEFAULT NULL,
  `exame_fisico` text DEFAULT NULL,
  `estresse_emocional` text DEFAULT NULL,
  `dados_extras` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atividade_fisica` text DEFAULT NULL,
  `hipotese_diagnostica` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `campos_anamnese`
--

CREATE TABLE `campos_anamnese` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `tipo` enum('textarea','text','select') DEFAULT 'textarea',
  `opcoes` text DEFAULT NULL,
  `obrigatorio` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `campos_anamnese`
--

INSERT INTO `campos_anamnese` (`id`, `nome`, `label`, `tipo`, `opcoes`, `obrigatorio`, `ativo`, `ordem`) VALUES
(1, 'queixa_principal', 'Queixa Principal', 'textarea', NULL, 1, 1, 1),
(2, 'historia_doenca', 'História da Doença Atual', 'textarea', NULL, 0, 1, 2),
(3, 'antecedentes_pessoais', 'Antecedentes Pessoais', 'textarea', NULL, 0, 1, 3),
(4, 'antecedentes_familiares', 'Antecedentes Familiares', 'textarea', NULL, 0, 1, 4),
(5, 'habitos', 'Hábitos e Estilo de Vida', 'textarea', NULL, 0, 1, 5),
(6, 'alimentacao', 'Alimentação', 'textarea', NULL, 0, 1, 6),
(7, 'sono', 'Qualidade do Sono', 'textarea', NULL, 0, 1, 7),
(8, 'atividade_fisica', 'Atividade Física', 'textarea', NULL, 0, 1, 8),
(9, 'medicamentos', 'Medicamentos / Suplementos', 'textarea', NULL, 0, 1, 9),
(10, 'alergias', 'Alergias', 'text', NULL, 0, 1, 10),
(11, 'exame_fisico', 'Exame Físico', 'textarea', NULL, 0, 1, 11),
(12, 'hipotese_diagnostica', 'Hipótese Diagnóstica', 'textarea', NULL, 0, 1, 12);

-- --------------------------------------------------------

--
-- Estrutura da tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_hora` datetime NOT NULL,
  `duracao` int(11) NOT NULL DEFAULT 60,
  `tipo` varchar(50) NOT NULL DEFAULT 'consulta',
  `status` varchar(20) NOT NULL DEFAULT 'Agendado',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `profissao` varchar(100) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `numero_filhos` tinyint UNSIGNED DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pacientes`
--

INSERT INTO `pacientes` (`id`, `nome`, `data_nascimento`, `cpf`, `rg`, `telefone`, `celular`, `email`, `endereco`, `cidade`, `estado`, `cep`, `profissao`, `estado_civil`, `numero_filhos`, `sexo`, `foto_path`, `observacoes`, `ativo`, `created_at`, `updated_at`) VALUES
(12, 'Kalleby da Silva Schultz', '2006-12-20', '05348848069', NULL, NULL, '51991242284', 'kallebyschultz@gmail.com', 'General Osório, 894', 'Venâncio Aires', NULL, NULL, 'Técnico em Informática', 'Solteiro(a)', NULL, 'M', NULL, 'teste', 1, '2026-04-01 18:55:30', '2026-04-01 18:55:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `prontuario`
--

CREATE TABLE `prontuario` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_atendimento` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_atendimento` varchar(50) NOT NULL DEFAULT 'consulta',
  `subjetivo` text DEFAULT NULL,
  `objetivo` text DEFAULT NULL,
  `avaliacao` text DEFAULT NULL,
  `plano` text DEFAULT NULL,
  `prescricao` text DEFAULT NULL,
  `retorno` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `prontuario_historico`
--

CREATE TABLE `prontuario_historico` (
  `id` int(11) NOT NULL,
  `prontuario_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `editado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atendimento` timestamp NULL DEFAULT NULL,
  `tipo_atendimento` varchar(50) DEFAULT NULL,
  `subjetivo` text DEFAULT NULL,
  `prescricao` text DEFAULT NULL,
  `retorno` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `cargo` varchar(50) NOT NULL DEFAULT 'medico',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cargo`, `ativo`, `created_at`) VALUES
(1, 'Administrador', 'admin@enterclinic.com', '$2y$10$D3cp8Kpd24TV.4Bj9Q84ZuYqqz8T5.OZp4BOSkAat93wc00CH1xsq', 'admin', 1, '2026-04-01 13:37:52');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `anamnese`
--
ALTER TABLE `anamnese`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `campos_anamnese`
--
ALTER TABLE `campos_anamnese`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices para tabela `prontuario_historico`
--
ALTER TABLE `prontuario_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prontuario_id` (`prontuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `prontuario`
--
ALTER TABLE `prontuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anamnese`
--
ALTER TABLE `anamnese`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `campos_anamnese`
--
ALTER TABLE `campos_anamnese`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `prontuario_historico`
--
ALTER TABLE `prontuario_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prontuario`
--
ALTER TABLE `prontuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `anamnese`
--
ALTER TABLE `anamnese`
  ADD CONSTRAINT `anamnese_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `anamnese_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `consultas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `prontuario_historico`
--
ALTER TABLE `prontuario_historico`
  ADD CONSTRAINT `prontuario_historico_ibfk_1` FOREIGN KEY (`prontuario_id`) REFERENCES `prontuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prontuario_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `prontuario`
--
ALTER TABLE `prontuario`
  ADD CONSTRAINT `prontuario_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `prontuario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Migração: adiciona updated_at à tabela prontuario (caso já exista o banco)
-- ALTER TABLE `prontuario` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- Migração: cria tabela de histórico de edições do prontuário (caso o banco já exista)
-- CREATE TABLE IF NOT EXISTS `prontuario_historico` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `prontuario_id` int(11) NOT NULL,
--   `usuario_id` int(11) NOT NULL,
--   `editado_em` timestamp NOT NULL DEFAULT current_timestamp(),
--   `data_atendimento` timestamp NULL DEFAULT NULL,
--   `tipo_atendimento` varchar(50) DEFAULT NULL,
--   `subjetivo` text DEFAULT NULL,
--   `prescricao` text DEFAULT NULL,
--   `retorno` text DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   KEY `prontuario_id` (`prontuario_id`),
--   KEY `usuario_id` (`usuario_id`),
--   CONSTRAINT `prontuario_historico_ibfk_1` FOREIGN KEY (`prontuario_id`) REFERENCES `prontuario` (`id`) ON DELETE CASCADE,
--   CONSTRAINT `prontuario_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
