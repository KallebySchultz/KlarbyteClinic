<div align="center">

<img src="assets/img/logo.png" alt="EnterClinic Logo" width="180"/>

**Sistema de Gestão Clínica — simples, completo e pronto para uso local**

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-Compatible-FB7A24?logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![Licença MIT](https://img.shields.io/badge/Licença-MIT-green)](LICENSE)

</div>

---

## 📋 Sobre o Projeto

O **KlarbyteClinic** é um sistema web de gestão clínica desenvolvido em PHP puro com banco de dados MySQL. Projetado para rodar localmente via XAMPP, ele oferece uma interface moderna e intuitiva para profissionais de saúde gerenciarem pacientes, consultas, prontuários e exames — tudo em um único lugar.

> 💡 Ideal para clínicas de pequeno e médio porte, consultórios e profissionais autônomos da área da saúde.

---

## 🖥️ Capturas de Tela

<div align="center">

| Dashboard | Pacientes |
|:---------:|:---------:|
| ![Dashboard](<capturas de tela/a (1).jpeg>) | ![Pacientes](<capturas de tela/a (2).jpeg>) |

| Prontuário | Consultas |
|:----------:|:---------:|
| ![Prontuário](<capturas de tela/a (3).jpeg>) | ![Consultas](<capturas de tela/a (4).jpeg>) |

| Anamnese | Exames |
|:--------:|:------:|
| ![Anamnese](<capturas de tela/a (5).jpeg>) | ![Exames](<capturas de tela/a (6).jpeg>) |

| Configurações | Imprimir Prontuário |
|:-------------:|:-------------------:|
| ![Configurações](<capturas de tela/a (7).jpeg>) | ![Imprimir](<capturas de tela/a (8).jpeg>) |

</div>

---

## ✨ Funcionalidades

### 👥 Gestão de Pacientes
- Cadastro completo com dados pessoais (nome, CPF, data de nascimento, sexo, estado civil, profissão, endereço, contatos)
- Edição e visualização do perfil do paciente
- Listagem com busca e filtros

### 📋 Anamnese Dinâmica
- Campos 100% personalizáveis pelo próprio usuário
- Suporte a campos de texto curto, área de texto e seleção
- Configuração de ordem, obrigatoriedade e ativação/desativação por campo

### 📅 Consultas
- Agendamento com data, hora e duração
- Tipos configuráveis (consulta, retorno, avaliação, etc.)
- Status de acompanhamento: **Agendado**, **Confirmado**, **Realizado**, **Cancelado**
- Agenda do dia diretamente no dashboard

### 📘 Prontuários (SOAP)
- Registro clínico estruturado nos campos:
  - **S** — Subjetivo
  - **O** — Objetivo
  - **A** — Avaliação
  - **P** — Plano
  - Prescrição e retorno
- Histórico completo de edições
- Impressão formatada do prontuário em PDF/papel

### 🔬 Exames
- Upload de arquivos de exames (PDF, imagens, etc.)
- Associação direta ao paciente
- Download e exclusão de arquivos

### ⚙️ Configurações do Sistema
- Personalização dos campos da anamnese
- Adição de novos campos dinâmicos
- Reordenação e definição de campos obrigatórios

### 📊 Dashboard
- Total de pacientes cadastrados
- Consultas do dia
- Total de registros em prontuário
- Agenda do dia com status
- Últimos pacientes cadastrados

---

## 🛠️ Tecnologias Utilizadas

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| PHP | 8.0+ | Backend / lógica de negócio |
| MySQL / MariaDB | 10.4+ | Banco de dados relacional |
| PDO | — | Acesso seguro ao banco de dados |
| HTML5 / CSS3 | — | Interface do usuário |
| JavaScript | — | Interações no frontend |
| XAMPP | — | Ambiente de desenvolvimento local |

---

## 🚀 Instalação e Configuração

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/) instalado (Apache + MySQL + PHP 8.0+)
- Navegador moderno (Chrome, Firefox, Edge)

> 📄 Consulte também o arquivo **`Tutorial de Instalação EnterClinic.pdf`** incluído no repositório para um guia visual passo a passo.

---

### Passo a Passo

**1. Clone ou baixe o repositório**

```bash
git clone https://github.com/KallebySchultz/KlarbyteClinic.git
```

Mova a pasta para o diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\KlarbyteClinic\
```

---

**2. Inicie o XAMPP**

Abra o Painel de Controle do XAMPP e inicie os serviços:
- ✅ **Apache**
- ✅ **MySQL**

---

**3. Crie o banco de dados**

Acesse o **phpMyAdmin** em `http://localhost/phpmyadmin` e:

1. Crie um banco de dados chamado `enterclinic`
2. Selecione o banco criado e clique em **Importar**
3. Selecione o arquivo `database.sql` da pasta do projeto
4. Clique em **Executar**

---

**4. Configure a conexão**

Abra o arquivo `config.php` e ajuste as credenciais conforme seu ambiente:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // senha do MySQL (padrão XAMPP: vazia)
define('DB_NAME', 'enterclinic');
```

---

**5. Acesse o sistema**

Abra o navegador e acesse:

```
http://localhost/KlarbyteClinic/
```

O repositório inclui o guia **`Como transformar o web em programa.pdf`**, que explica como empacotar o EnterClinic em um executável desktop usando ferramentas como Nativefier ou similares, para que funcione como um programa instalado no computador.

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Faça um **fork** do repositório
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Faça commit das alterações: `git commit -m 'Adiciona minha feature'`
4. Faça push para a branch: `git push origin feature/minha-feature`
5. Abra um **Pull Request**

---

## 📄 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

---

## 👨‍💻 Autor

Desenvolvido por **Kalleby Schultz**

[![GitHub](https://img.shields.io/badge/GitHub-KallebySchultz-181717?logo=github)](https://github.com/KallebySchultz)

---

<div align="center">

Feito com carinho para facilitar a gestão clínica.

</div>
