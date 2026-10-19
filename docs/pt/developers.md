# 🧩 Para desenvolvedores

Outros plugins podem criar **itens manuais** num curso por meio de uma pequena API pública. Para o professor os itens parecem itens manuais comuns, mas são marcados com o componente de **origem**, de modo que o plugin dono pode substituir seu próprio conjunto a qualquer momento sem mexer nos itens que o professor adicionou à mão nem nos itens criados por outros plugins.

```php
\block_teacher_checklist\local\external_tasks::replace(
    $course->id,
    'local_myplugin',
    ['Revisar o cronograma semanal', 'Publicar a primeira atividade']
);
```

* `replace(int $courseid, string $source, array $titles)` remove os itens criados antes sob o mesmo `$source` e os recria a partir de `$titles`, na ordem. Títulos em branco são ignorados. Um array vazio limpa a origem.
* O método faz escritas destrutivas e **não controla acesso**: quem chama **deve** verificar `moodle/course:update` no contexto do curso antes.
* Proteja a chamada com `class_exists(\block_teacher_checklist\local\external_tasks::class)` para manter a integração opcional.
