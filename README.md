[English](#eng) | [Русский](#rus)

# <a name="eng"></a> Simple RSS Editor
## Required GET-parameter
* `url` — RSS feed URL for processing

## Optional GET-parameters: purpose and processing order
|   | Parameter name             | Value type                              | Sense of value element   | What Does It Do                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
|---|----------------------------|-----------------------------------------|--------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 1 | `amp`                      | presence/absence                        | do/don't do              | It replaces all `&amp;` occurrences with `&` (cases like `&amp;amp;`, `&amp;quot;`, etc.)                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| 2 | *[it's always executed]*   | —                                       | —                        | It replaces all HTML entities with appropriate number code in accord with XML standard, e.g. replacing `&quot;` with `&#34;`                                                                                                                                                                                                                                                                                                                                                                                                      |
| 3 | `add_namespace`            | string                                  | namespace                | It adds namespace by means of `add_namespace` value pasting in the attribute list of `rss` tag                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| 4 | `remove`                   | string or array of strings              | tag name                 | It removes all listed tags                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| 5 |`rename_from` and `rename_to`|strings or same length arrays of strings| tag name before and after| It renames `rename_from` tag to `rename_to`. If parameters are arrays then rename `rename_from[i]` tag to `rename_to[i]` for each `i`                                                                                                                                                                                                                                                                                                                                                                                             |
| 6 | `split`                    | string or array of strings              | tag name                 | It splits tag content into words (assumption: tag content in CamelCase style) and put each word into the new tags with the same name                                                                                                                                                                                                                                                                                                                                                                                              |
| 7 | `replace_description`      | string                                  | [XPath 1.0 expression](http://www.w3schools.com/xsl/xpath_intro.asp) | It extracts values from a webpage of each RSS item (webpage URL is extracted from a `<link>` if it exists, otherwise item is ignored) using XPath expression and replaces content of `<description>` element with extracted joint (with separator `PHP_EOL`) value. **Note**: web page content is being extracted as is, in other words if XPath expression extracts whole HTML tags then description will be contain them.                                                           |
| 8 | `extend_description`       | string                                  | [XPath 1.0 expression](http://www.w3schools.com/xsl/xpath_intro.asp) | It extracts values from a webpage of each RSS item (webpage URL is extracted from a `<link>` if it exists, otherwise item is ignored) using XPath expression and appends extracted joint (with separator `PHP_EOL`) value to content of `<description>` element. **Note**: web page content is being extracted as is, in other words if XPath expression extracts whole HTML tags then description will be contain them.                                                              |
| 9 | `break`                    | string or array of strings              | tag name                 | It pastes `<br/>` into the tag content instead of each `\n` and `\r` character (multiple consecutive characters replaced with single `<br/>`)                                                                                                                                                                                                                                                                                                                                                                                     |
|10 | `cdata`                    | string or array of strings              | tag name                 | It wraps tag content in `<![CDATA[ ... ]]>` block                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
|11 |`replace_from`, `replace_to`| string or array of strings              | regular expression       | These and the two following parameters are for a tag content replacement. It replaces each match of `replace_from` (or `replace_from[i]`) regexp in the content of tag `replace_in` (or `replace_in[i]`) in accord with `replace_to` (or `replace_to[i]`) regexp. Regexps are in PCRE notation, but starting and ending `/` character **must be absent**. These parameters can be used for the ordinary replacement, but <code>\ + * ? [ ^ ] $ ( ) { } = ! < > &#124; : -</code> characters must be escaped  by a `\` character (e.g, `\(`). |
|11 | `replace_in`               | string or array of strings              | tag name                 | Tag which content will be replaced. If its value is `*` then replacement will be in **each** tag of feed (including `<pubDate>`, `<link>`, etc.).                                                                                                                                                                                                                                                                                                                                                                                 |
|11 | `replace_sens`             | presence/absence                        | match case or not        | Case sensitivity. If `replace_sens` (or `replace_sens[i]`) is passed then replacement will be case sensitive else not.                                                                                                                                                                                                                                                                                                                                                                                                            |
|12 | `add_category`             | string                                  | [XPath 1.0 expression](http://www.w3schools.com/xsl/xpath_intro.asp) | It extracts values from a webpage of each RSS item (webpage URL is extracted from a `<link>` if it exists, otherwise item is ignored) using XPath expression and add `<category>` elements (to the appropriate RSS item) for each extracted value. XPath-expression must extract the final text (array of texts) only                                                                                                                                                                 |
|13 | `include`                  | string or array of string               | Regular expression (PCRE) without wrapped slashes `/` | It leaves items where title, description or category matches at least one regular expression                                                                                                                                                                                                                                                                                                                                                                                                         |
|14 | `exclude`                  | string or array of string               | Regular expression (PCRE) without wrapped slashes `/` | It leaves items where title, description and category do not match all regular expressions                                                                                                                                                                                                                                                                                                                                                                                                          |

## Script constants
Several configuration constants are defined at the beginning of [index.php](index.php) script
that can be modified:
* `DEBUG` is whether to use debug mode (make some additional actions for debugging if it's enabled).
  *Default value*: `false`.
* `MIN_DOWNLOAD_DELAY` is the minimum delay in seconds between RSS-elements' web-pages fetching.
  Every actual delay is a random value between minimum and maximum values.
  *Default value*: `0.12` (seconds).
* `MAX_DOWNLOAD_DELAY` is the maximum delay in seconds between RSS-elements' web-pages fetching.
  Every actual downloading delay is a random value between minimum and maximum values.
  *Default value*: `0.94` (seconds).
* `USE_WEBPAGE_CACHING` is whether to cache downloaded web-pages to reuse if it's necessary.
  *Default value*: `true`.


## Examples
* Replacing `&amp;` with `&` in the tags content,
  adding `xmlns:yandex="http://news.yandex.ru/"` namespace,
  removing `description` tag,
  renaming `full-text` tag (from `yandex` namespace) to `description`,
  wrapping content of `title` and `description` tags into `CDATA` block,
  adding `<br/>` instead of break lines characters in the `description` tag content,
  also replacing all `ё` occurrences with `е` (case insensitive) in the`description` tag content:
  in the RSS feed http://milknews.ru/index/novosti-moloko.rss
  ```
  index.php?url=http%3A%2F%2Fmilknews.ru%2Findex%2Fnovosti-moloko.rss&amp&add_namespace=xmlns%3Ayandex%3D%22http%3A%2F%2Fnews.yandex.ru%2F%22&remove=description&rename_from=full-text&rename_to=description&cdata[]=title&cdata[]=description&break=description&replace_from=ё&replace_to=е&replace_in=description
  ```

* Replacing description of each news with content of news web-page,
  that matches XPath expression `//div[@class='m-block__text-wrapper']/p`
  in the RSS feed http://www.fontanka.ru/fontanka.rss
  ```
  index.php?url=http%3A%2F%2Fwww.fontanka.ru%2Ffontanka.rss&replace_description=%2F%2Fdiv%5B%40class%3D%27m-block__text-wrapper%27%5D%2Fp
  ```

* Filtering RSS items: it leaves only items that contains substring 'font' and does not contain 'fonts'
  ```
  index.php?url=https://feeds.feedburner.com/CssTricks&include=font&exclude=fonts
  ```

# <a name="rus"></a> Простенький редактор RSS-ленты
## Обязательный GET-параметр
* `url` — URL RSS-ленты для обработки

## Необязательные GET-параметры: возможности и порядок их выполнения

|   | Имя параметра              | Тип каждого из параметров               | Семантика элемента типа  | Действие                                                                                                                                                                                                              |
|---|----------------------------|-----------------------------------------|--------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 1 | `amp`                      | наличие/отсутствие                      | делать/не делать         | замена всех вхождений `&amp;` на `&` (для случаев вида `&amp;amp;`, `&amp;quot;` и т.п.)                                                                                                                              |
| 2 | *[автоматически всегда]*   | —                                       | —                        | замена всех HTML-сущностей на их числовой код, чтобы соответствовать XML, например, `&quot;` заменяется на `&#34;`                                                                                                    |
| 3 | `add_namespace`            | строка                                  | пространство имен        | добавление пространства имен путем простой вставки содержимого `add_namespace` в список атрибутов тега `rss`                                                                                                          |
| 4 | `remove`                   | строка или массив строк                 | имя тега                 | удаление всех тегов с перечисленными именами                                                                                                                                                                          |
| 5 | `rename_from` и `rename_to`| строки или одинаковых длин массивы строк| имя тега до и после      | переименование тега с именем `rename_from` в имя `rename_to`, а в случае массивов переименование тега с именем `rename_from[i]` в `rename_to[i]`                                                                      |
| 6 | `split`                    | строка или массив строк                 | имя тега                 | разбиение содержимого тега с заданным именем на отдельные слова (предполагая, что изначальное содержимое оформлено в стиле CamelCase) и помещение их  в нижнем регистре в отдельные теги с тем же изначальным именем  |
| 7 | `replace_description`      | string                                  | [XPath 1.0 expression](http://www.w3schools.com/xsl/xpath_intro.asp) | Заменяет содержимое `<description>` каждого RSS-элемента на извлеченое XPath-выражением значение (в случае нескольких значений их содержимое конкатенируется с разделителем новая строка — `PHP_EOL` ) с веб-страницы RSS-элемента (адрес которой извлекается из `<link>`, если он есть, иначе RSS-элемент игнорируется). **Замечание**: контект с веб-страницы извлекается как есть, иными словами, если XPath-выражение извлекает целые теги, то в описании будет этот же HTML-код. |
| 8 | `extend_description`       | string                                  | [XPath 1.0 expression](http://www.w3schools.com/xsl/xpath_intro.asp) | Дополняет содержимое `<description>` каждого RSS-элемента извлеченым XPath-выражением значением (в случае нескольких значений их содержимое конкатенируется с разделителем новая строка — `PHP_EOL` ) с веб-страницы RSS-элемента (адрес которой извлекается из `<link>`, если он есть, иначе RSS-элемент игнорируется). **Замечание**: контект с веб-страницы извлекается как есть, иными словами, если XPath-выражение извлекает целые теги, то в описании будет этот же HTML-код. |
| 9 | `break`                    | строка или массив строк                 | имя тега                 | вставка `<br/>` внутри каждого тега с заданным именем заместо переносов строк `\n` и `\r` (если идет несколько переносов подряд, то заменяется лишь на одну вставку `<br/>`)                                          |
|10 | `cdata`                    | строка или массив строк                 | имя тега                 | заворачивание содержимого тегов с заданным именем в структуру `<![CDATA[ ... ]]>`                                                                                                                                     |
|11 |`replace_from`, `replace_to`| строка или массив строк                 | регулярное выражение     | эти и два последующих параметра служат для замен в оборачиваемых тегами текстах. В теге с именем `replace_in` всякое соответствие регулярному выражению `replace_from` (в нотации PCRE, **без** окаймляющих символов `/`) заменяется в соответствии с регулярным выражением  `replace_to`.   Параметры могут использоваться для обычной замены, только любой из символов <code>\ + * ? [ ^ ] $ ( ) { } = ! < > &#124; : -</code> должен быть экранирован символов `\` (например, `\(`).|
|11 | `replace_in`               | строка или массив строк                 | имя тега                 | может принимать специальное значение `*`, что приведёт к поиску и замене соответствий во всех тегах, включая служебные (`<pubDate>`, `<link>` и др.).                                                                 |
|11 | `replace_sens`             | наличие/отсутствие или массив наличий/отсутствий | учитывать регистр или нет | Если для текущей пары регулярных выражений присутствует `replace_sens`, то поиск ведётся с учётом регистра, иначе без учёта.                                                                                |
|12 | `add_category`             | строка                                  | [XPath-выражение 1.0](http://www.w3schools.com/xsl/xpath_intro.asp) | добавление каждому RSS-элементу категорий `<category>`, содержимое которых определяется XPath-выражением (XPath 1.0), извлекающее список чего-либо со страницы этого RSS-элемента (адрес берётся из тега `link`, если он есть), впоследствии преобразуемое в список строк. XPath-выражение должно извлекать сразу нужную информацию — значение содержимого или атрибута тега |
|13 | `include`                  | строка или массив строк                 | Регулярное выражение в стиле PCRE без символа `/` в начале и конце | оставлять только те RSS-элементы, чьи заголовок, описание или категории соответстувуют хотя бы одному регулярному выражению |
|14 | `exclude`                  | строка или массив строк                 | Регулярное выражение в стиле PCRE без символа `/` в начале и конце | оставлять только те RSS-элементы, чьи заголовок, описание и категории не соответстувуют всем регулярным выражениям |

Пример работы параметра `split`: подано значение `split=tag`, поэтому было `<tag>КрепостьЧПЗдоровьеНебо</tag>`, стало `<tag>крепость</tag><tag>чп</tag><tag>здоровье</tag><tag>небо</tag>`

## Константы скрипта
В начале скрипта [index.php](index.php) определены несколько конфигурационных констант,
которые можно менять на свое усмотрение:
* `DEBUG` — использовать или нет режим отладки (по факту режим отладки лишь делает некоторые дополнительные действия).
  *Значение по умолчанию*: `false`.
* `MIN_DOWNLOAD_DELAY` — минимальная задержка в секундах между загрузками веб-страниц RSS-элементов.
  Величина задержки между загрузками веб-страниц представляет собой случайное значение между минимальным и максимальным значениями.
  *Значение по умолчанию*: `0.12` (секунд).
* `MAX_DOWNLOAD_DELAY` — максимальная задержка в секундах между загрузками веб-страниц RSS-элементов.
  Величина задержки между загрузками веб-страниц представляет собой случайное значение между минимальным и максимальным значениями.
  *Значение по умолчанию*: `0.94` (секунд).
* `USE_WEBPAGE_CACHING` — кэшировать или нет загруженные веб-страницы для повторного использования в случае необходимости.
  *Значение по умолчнию*: `true`.

## Примеры использования
* В RSS-ленте http://milknews.ru/index/novosti-moloko.rss
  замена `&amp;` на `&`,
  добавление пространства имен `xmlns:yandex="http://news.yandex.ru/"`,
  удаление тега `description`,
  переименования тега `full-text` (из пространства `yandex`) в `description`,
  заворачивание в `CDATA` содержимое тегов `title` и `description`,
  а также добавление `<br/>` заместо переносов строк в содержимом тега `description`,
  дополнительно к этому проводится замена всех вхождений
  буквы `ё` на `е` (без учёта регистра) в тегах с именем `description`:
  ```
  index.php?url=http%3A%2F%2Fmilknews.ru%2Findex%2Fnovosti-moloko.rss&amp&add_namespace=xmlns%3Ayandex%3D%22http%3A%2F%2Fnews.yandex.ru%2F%22&remove=description&rename_from=full-text&rename_to=description&cdata[]=title&cdata[]=description&break=description&replace_from=ё&replace_to=е&replace_in=description
  ```

* В RSS-ленте http://www.fontanka.ru/fontanka.rss
  замена описания новостей на то,
  что содержится на веб-странице новости, соответствующее XPath-выражению
  `//div[@class='m-block__text-wrapper']/p`
  ```
  index.php?url=http%3A%2F%2Fwww.fontanka.ru%2Ffontanka.rss&replace_description=%2F%2Fdiv%5B%40class%3D%27m-block__text-wrapper%27%5D%2Fp
  ```
* Фильтрация RSS: оставляет только те RSS-элементы, в которых упоминается строка 'font', но при этом не упоминается строка 'fonts'
  ```
  index.php?url=https://feeds.feedburner.com/CssTricks&include=font&exclude=fonts
  ```
 