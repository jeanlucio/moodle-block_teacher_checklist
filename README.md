# Teacher Checklist Block

![Moodle Badge](https://img.shields.io/badge/Moodle-4.5%2B-orange)
![License](https://img.shields.io/badge/License-GPLv3-blue)

**[English](#english) | [Português](#português) | [Español](#español) | [Français](#français)**

---

<a name="english"></a>
## 🇬🇧 English

The **Teacher Checklist** is a block for Moodle that helps teachers track the progress of their course setup. It combines automatic issue detection with a manual to-do list.

### ✨ Features

#### 1. Automatic Scanning (Smart Detection)
The plugin automatically scans the course and alerts the teacher about:
* **Course Visibility:** Warns if the course is hidden from students.
* **Gradebook Empty:** Detects if the course has no grade items configured.
* **Assignment Issues:** Identifies assignments without due dates, descriptions, or with pending grading.
* **Quiz Issues:** Flags quizzes without questions, without time limits/close dates, or attempts waiting for manual grading.
* **Empty Forums:** Detects forums without any discussion topics.
* **Activity Completion:** Warns about visible activities with completion tracking disabled.
* **Empty Sections:** Identifies visible course sections that have no content.

#### 2. Manual Items
Teachers can add their own tasks (e.g., "Print exams", "Record welcome video").
* **Smart Linking:** If the manual task title matches an existing activity name, a link is automatically created.
* **Safe Backup:** Manual items are preserved during course backup/restore, while automatic issues are recalculated dynamically.

### 🚀 Installation
1.  Unzip the content into the `blocks/teacher_checklist` directory of your Moodle.
2.  Visit the "Notifications" page in Site Administration to trigger the installation.
3.  Add the block to your course via "Turn editing on" > "Add a block".

### 📋 Requirements
* Moodle 4.5 or higher.
* PHP 7.4 or higher.

---

<a name="português"></a>
## 🇧🇷 Português

O **Teacher Checklist** é um bloco para Moodle que ajuda professores a acompanhar o progresso da configuração de seus cursos. Ele combina detecção automática de problemas com uma lista de tarefas manual.

### ✨ Funcionalidades

#### 1. Verificação Automática (Detecção Inteligente)
O plugin varre automaticamente o curso e alerta o professor sobre:
* **Visibilidade do Curso:** Avisa se o curso está oculto para alunos.
* **Livro de Notas:** Detecta se o curso não possui itens de avaliação configurados.
* **Problemas em Tarefas:** Identifica tarefas sem data de entrega, sem descrição ou com notas pendentes.
* **Problemas em Questionários:** Sinaliza questionários sem perguntas, sem limite de tempo/data de fechamento ou com tentativas aguardando correção manual.
* **Fóruns Vazios:** Detecta fóruns sem tópicos de discussão.
* **Conclusão de Atividade:** Avisa sobre atividades visíveis com o rastreamento de conclusão desligado.
* **Tópicos Vazios:** Identifica seções visíveis do curso que não possuem conteúdo.

#### 2. Itens Manuais
Professores podem adicionar suas próprias tarefas (ex: "Imprimir provas", "Gravar vídeo de boas-vindas").
* **Link Inteligente:** Se o título da tarefa manual for igual ao nome de uma atividade existente, um link é criado automaticamente.
* **Backup Seguro:** Itens manuais são preservados durante o backup/restauração do curso.

### 🚀 Instalação
1.  Descompacte o conteúdo no diretório `blocks/teacher_checklist` do seu Moodle.
2.  Visite a página "Notificações" na Administração do Site para instalar.
3.  Adicione o bloco ao curso ativando a edição e clicando em "Adicionar um bloco".

### 📋 Requisitos
* Moodle 4.5 ou superior.

---

<a name="español"></a>
## 🇪🇸 Español

El **Teacher Checklist** es un bloque para Moodle que ayuda a los profesores a seguir el progreso de la configuración de sus cursos. Combina la detección automática de problemas con una lista de tareas manual.

### ✨ Características

#### 1. Escaneo Automático
El plugin escanea automáticamente el curso y alerta sobre:
* **Visibilidad del Curso:** Advierte si el curso está oculto a los estudiantes.
* **Libro de Calificaciones:** Detecta si el curso no tiene ítems de calificación configurados.
* **Problemas con Tareas:** Identifica tareas sin fecha de entrega, descripción o con calificaciones pendientes.
* **Problemas con Cuestionarios:** Señala cuestionarios sin preguntas, sin límite de tiempo/fecha de cierre o intentos esperando calificación manual.
* **Foros Vacíos:** Detecta foros sin temas de discusión.
* **Finalización de Actividad:** Advierte sobre actividades visibles con el rastreo de finalización desactivado.
* **Secciones Vacías:** Identifica secciones visibles del curso que no tienen contenido.

#### 2. Ítems Manuales
Los profesores pueden añadir sus propias tareas (ej: "Imprimir exámenes").
* **Enlace Inteligente:** Si el título coincide con una actividad existente, se crea un enlace automáticamente.
* **Copia de Seguridad Segura:** Los ítems manuales se conservan durante la copia de seguridad/restauración.

### 📋 Requisitos
* Moodle 4.5 o superior.

---

<a name="français"></a>
## 🇫🇷 Français

**Teacher Checklist** est un bloc pour Moodle qui aide les enseignants à suivre la configuration de leurs cours. Il combine la détection automatique de problèmes avec une liste de tâches manuelle.

### ✨ Fonctionnalités

#### 1. Analyse Automatique
Le plugin analyse automatiquement le cours et alerte sur :
* **Visibilité du Cours :** Avertit si le cours est caché aux étudiants.
* **Carnet de Notes :** Détecte si le cours n'a pas d'éléments d'évaluation configurés.
* **Problèmes de Devoirs :** Identifie les devoirs sans date limite, description ou en attente de notation.
* **Problèmes de Tests :** Signale les tests sans questions, sans limite de temps/date de fermeture ou en attente de notation manuelle.
* **Forums Vides :** Détecte les forums sans sujets de discussion.
* **Achèvement d'Activité :** Avertit des activités visibles avec le suivi d'achèvement désactivé.
* **Sections Vides :** Identifie les sections visibles du cours qui n'ont pas de contenu.

#### 2. Tâches Manuelles
Les enseignants peuvent ajouter leurs propres tâches (ex : "Imprimer les examens").
* **Lien Intelligent :** Si le titre correspond à une activité existante, un lien est créé automatiquement.
* **Sauvegarde Sécurisée :** Les éléments manuels sont conservés lors de la sauvegarde/restauration.

### 📋 Prérequis
* Moodle 4.5 ou supérieur.
