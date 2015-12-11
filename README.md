# Simple RSS Editor

## Обязательный параметр
* `url` — URL на обрабатываемый RSS

## Необязательные параметры: возможности и порядок их выполнения

|   | Имя параметра              | Тип                                     | Семантика элемента типа  | Действие                                                                                                                                                                                           |
|---|----------------------------|-----------------------------------------|--------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 1 | `amp`                      | наличие/отсутствие                      | делать/не делать         | замена всех вхождений `&amp;` на `&`                                                                                                                                                               |
| 2 | [автоматически всегда]     | —                                       | —                        | замена всех HTML-сущностей на их числовой код, чтобы соответствовать XML, например, `&quot;` заменяется на `&#34;`                                                                                 |
| 3 | `add_namespace`            | строка                                  | прострнство имен         | добавление пространства имен путем простой вставки содержимого `add_namespace` в список атрибутов тега `rss`                                                                                       |
| 4 | `remove`                   | строка или массив строк                 | имя тега                 | удаление всех тегов с перечисленными именами                                                                                                                                                       |
| 5 | `rename_from` и `rename_to`| строки или одинаковых длин массивы строк| имя тега до и после      | переименование тега с именем `rename_from` в имя `rename_to`, а в случае массивов аналогичное действие выполняется для каждой пары элементов, сгруппированных по одинаковым индексам               |
| 6 | `split`                    | строка или массив строк                 | имя тега                 | разбиение содержимого тега с заданным именем на отдельные слова (предполагая, что изначальное содержимое оформлено в стиле CamelCase) и помещение их в отдельные теги с тем же изначальным именем  |
| 7 | `break`                    | строка или массив строк                 | имя тега                 | вставка `<br/>` внутри каждого тега с заданным именем заместо переносов строк `\n` и `\r` (если идет несколько переносов подряд, то заменяется лишь на одну вставку `<br/>`)                       |
| 8 | `cdata`                    | строка или массив строк                 | имя тега                 | заворачивание содержимого тегов с заданным именем в структуру `<![CDATA[ ... ]]>`                                                                                                                  |

Пример работы параметра `split=tag`: было `<tag>КрепостьЧПЗдоровьеНебо</tag>`, стало `<tag>Крепость</tag><tag>ЧП</tag><tag>Здоровье</tag><tag>Небо</tag>`

## Пример использования
В RSS-ленте http://milknews.ru/index/novosti-moloko.rss замена `&amp;` на `&`, добавление пространства имен `xmlns:yandex="http://news.yandex.ru/"`, удаление тега `description`, переименования тега `full-text` (из пространства `yandex`) в `description`, заворачивание в `CDATA` содержимое тегов `title` и `description`, а также добавление `<br/>` заместо переносов строк в содержимом тега `description`:
```
index.php?url=http%3A%2F%2Fmilknews.ru%2Findex%2Fnovosti-moloko.rss&amp&add_namespace=xmlns%3Ayandex%3D%22http%3A%2F%2Fnews.yandex.ru%2F%22&remove=description&rename_from=full-text&rename_to=description&cdata[]=title&cdata[]=description&break=description
```
