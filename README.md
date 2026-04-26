# Teacher Checklist — Moodle Block

[![Moodle Plugin CI](https://github.com/jeanlucio/moodle-block_teacher_checklist/actions/workflows/ci.yml/badge.svg)](https://github.com/jeanlucio/moodle-block_teacher_checklist/actions/workflows/ci.yml)
![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv3-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-Stable-green?style=flat-square)

[English](#english) | [Português](#português)

---

## English

**Teacher Checklist** is a Moodle block that helps teachers track the quality of their course setup. It combines automatic issue detection with a manual to-do list so nothing slips through the cracks before students arrive.

---

### ✨ Features

* 🔍 **Automatic Scanning:** Detects common course configuration problems without any manual work.
* 📋 **Manual Items:** Teachers can add custom tasks that the system cannot detect automatically.
* 🔗 **Smart Linking:** If a manual task title matches an existing activity name, a clickable link is created automatically.
* 👁️ **Status Tracking:** Each item can be marked as Done, Ignored, or restored to Pending.
* ⚡ **Bulk Actions:** Mark or ignore multiple items at once with a single click.
* 💾 **Backup Safe:** Manual items are preserved during course backup/restore; automatic issues are recalculated dynamically.
* 🔄 **Toggle Scan:** Automatic scanning can be disabled to use the block as a pure manual checklist.

#### Automatic checks

| # | Check | What it detects |
|---|-------|----------------|
| 1 | Course visibility | Course is hidden from students |
| 2 | Course summary | Course has no summary or description |
| 3 | Course end date | Course has no end date configured |
| 4 | Gradebook | No grade items configured |
| 5 | Assignments | Missing due date, missing description, pending submissions |
| 6 | Quizzes | No questions, no time limit or close date, attempts awaiting manual grading |
| 7 | Forums | No discussion topics; no description (Announcements forum excluded) |
| 8 | Completion tracking | Visible activities with completion tracking disabled |
| 9 | Empty sections | Visible course sections with no content |

---

### 🎓 Educational Purpose

Teacher Checklist is designed to:

* Reduce course configuration errors before students access the course
* Give teachers a single place to track both technical issues and personal reminders
* Support quality assurance workflows at scale for course coordinators
* Lower support requests caused by misconfigured courses

Suitable for:

* Individual teachers who want a quality checklist before publishing their course
* Course coordinators reviewing multiple courses
* Institutions running Moodle with custom quality standards

---

### 📦 Requirements

| Component | Version |
|-----------|---------|
| Moodle    | 4.5+    |
| PHP       | 8.1+    |

---

### 🛠️ Installation

1. Download or clone this repository into `blocks/teacher_checklist` inside your Moodle root.
2. Visit **Site administration > Notifications** to run the database upgrade.
3. Add the **Teacher Checklist** block to any course page via **Turn editing on > Add a block**.

```bash
git clone git@github.com:jeanlucio/moodle-block_teacher_checklist.git blocks/teacher_checklist
```

---

### 📖 Usage

1. Add the **Teacher Checklist** block to your course page.
2. The block immediately shows any pending issues detected automatically.
3. Click **View full report** to open the dashboard with all tabs: Pending, Ignored, and Done.
4. Add manual tasks via the text field at the top of the dashboard.
5. Use the action buttons on each item to mark it as Done, Ignore it, or Restore it to Pending.
6. Use **Bulk Actions** to update multiple items at once after selecting them with the checkboxes.
7. Toggle **Automatic Scan** off if you want to use the block only as a manual checklist.

---

### 🔐 Security & Compliance

* Capability-based access control (`block/teacher_checklist:addinstance`)
* `require_sesskey()` on all state-changing form submissions
* Moodle External API compliant (AJAX via `core/ajax`)
* Full Privacy API implementation — data export and deletion supported
* Backup and restore support for manual items

---

## 📄 License / Licença

This project is licensed under the **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio

---

## Português

O **Teacher Checklist** é um bloco para Moodle que ajuda professores a verificar a qualidade da configuração de seus cursos. Ele combina detecção automática de problemas com uma lista de tarefas manual para que nada passe despercebido antes que os alunos acessem o curso.

---

### ✨ Funcionalidades

* 🔍 **Verificação Automática:** Detecta problemas comuns na configuração do curso sem nenhum trabalho manual.
* 📋 **Itens Manuais:** Professores podem adicionar tarefas que o sistema não consegue detectar automaticamente.
* 🔗 **Link Inteligente:** Se o título de uma tarefa manual coincidir com o nome de uma atividade existente, um link clicável é criado automaticamente.
* 👁️ **Rastreamento de Status:** Cada item pode ser marcado como Feito, Ignorado ou restaurado para Pendente.
* ⚡ **Ações em Lote:** Marque ou ignore vários itens de uma vez com um único clique.
* 💾 **Backup Seguro:** Itens manuais são preservados no backup/restauração do curso; problemas automáticos são recalculados dinamicamente.
* 🔄 **Alternar Verificação:** A verificação automática pode ser desativada para usar o bloco apenas como lista manual.

#### Verificações automáticas

| # | Verificação | O que detecta |
|---|-------------|---------------|
| 1 | Visibilidade do curso | Curso oculto para estudantes |
| 2 | Resumo do curso | Curso sem resumo ou descrição |
| 3 | Data de término | Curso sem data de término configurada |
| 4 | Livro de notas | Nenhum item de avaliação configurado |
| 5 | Tarefas | Sem data de entrega, sem descrição, envios pendentes de correção |
| 6 | Questionários | Sem perguntas, sem limite de tempo ou data de fechamento, tentativas aguardando correção manual |
| 7 | Fóruns | Sem tópicos de discussão; sem descrição (Fórum de Avisos excluído) |
| 8 | Rastreamento de conclusão | Atividades visíveis com rastreamento de conclusão desligado |
| 9 | Tópicos vazios | Seções visíveis do curso sem conteúdo |

---

### 🎓 Finalidade Educacional

O Teacher Checklist foi projetado para:

* Reduzir erros de configuração antes que os estudantes acessem o curso
* Oferecer ao professor um único lugar para acompanhar problemas técnicos e lembretes pessoais
* Apoiar fluxos de garantia de qualidade em escala para coordenadores de curso
* Diminuir chamados de suporte causados por cursos mal configurados

Indicado para:

* Professores que desejam uma lista de verificação de qualidade antes de publicar o curso
* Coordenadores de curso revisando múltiplos cursos
* Instituições que utilizam o Moodle com padrões de qualidade personalizados

---

### 📦 Requisitos

| Componente | Versão  |
|------------|---------|
| Moodle     | 4.5+    |
| PHP        | 8.1+    |

---

### 🛠️ Instalação

1. Baixe o arquivo `.zip` ou clone este repositório na pasta `blocks/teacher_checklist` do seu Moodle.
2. Acesse **Administração do site > Notificações** para executar a atualização do banco de dados.
3. Adicione o bloco **Teacher Checklist** a qualquer página de curso via **Ativar edição > Adicionar um bloco**.

```bash
git clone git@github.com:jeanlucio/moodle-block_teacher_checklist.git blocks/teacher_checklist
```

---

### 📖 Como Usar

1. Adicione o bloco **Teacher Checklist** à página do seu curso.
2. O bloco exibe imediatamente os problemas pendentes detectados automaticamente.
3. Clique em **Ver relatório completo** para abrir o painel com todas as abas: Pendentes, Ignorados e Feitos.
4. Adicione tarefas manuais pelo campo de texto no topo do painel.
5. Use os botões de ação em cada item para marcá-lo como Feito, Ignorá-lo ou Restaurá-lo para Pendente.
6. Use as **Ações em Lote** para atualizar vários itens de uma vez após selecioná-los com as caixas de seleção.
7. Desative a **Verificação Automática** se quiser usar o bloco apenas como lista manual.

---

### 🔐 Segurança e Conformidade

* Controle de acesso baseado em capabilities (`block/teacher_checklist:addinstance`)
* Proteção com `require_sesskey()` em todos os envios de formulário que alteram dados
* Compatível com a API externa do Moodle (AJAX via `core/ajax`)
* Implementação completa da Privacy API — exportação e exclusão de dados suportadas
* Suporte a backup e restauração de itens manuais

---

## 📄 Licença

Este projeto é licenciado sob a **GNU General Public License v3 (GPLv3)**.

**Copyright:** 2026 Jean Lúcio
