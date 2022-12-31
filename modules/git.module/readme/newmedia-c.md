#Контроллер NewMedia  [назад](index.md)
######/local/modules/git.module/lib/controllers/[newmedia.php](../../lib/controllers/newmedia.php)

##checkModules
подключем необходимые библиотеки для работы с Инфоблоками и Социальными сетями(команды)

##uploadFileAction
Получение чанков и загрузка на сервер <br>
####Принимает:
$dzUuid - Временное название файла <br>
$dzChunkIndex - Номер чанка
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.uploadFile')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors')
</pre>

##getListTempMediaAction
Получить список медиа файлов которые пользователь еще не менял
####Принимает:
Ничего
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getListTempMedia')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result =>
 array(),
 ...,
 array(
  date,
  file,
  id,
  link_user_media,
  link_user_media_edit,
  name,
  picture,
  size,
  size_format,
  type
 )
</pre>

##getShortMediaTempData [](#getShortMediaTempData)
Форматирование массива для фронта, страница сразу после добавления медиа
####Принимает:
$arFiles - Массив данных для форматирования
$arKey - Ключи которые нужно вернуть
####Вызывается:
`Private`

##getListFullMediaUserAction
Вернет все медиа которые загрузил пользователь. сортировка по ID
####Принимает:
Ничего
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getListFullMediaUser')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result =>
 array(),
 ...,
 array(Массив из битрикса, без форматирования)
</pre>

##getListMediaUserSortAction
Получить медиа пользователя с разбивкой по этапам и мероприятиям + формирование массива на фронт
####Принимает:
Ничего
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getListMediaUserSort')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => 
 array(), 
 ...,
 array(Массив из битрикса, без форматирования)
</pre>

##getShortArrayStage
Массив для фронта (этапы)
####Принимает:
$arStages - Массив этапов
####Вызывается:
`Private`
####Отдает:
<pre>
array(), 
...,
array(
 name,
 id,
 event_id,
 media => <a name="getShortMediaTempData">getShortMediaTempData</a>
)
</pre>

##getShortArrayEvent 
Массив для фронта (мероприятие)
####Принимает:
$arEvents - Массив мероприятий
####Вызывается:
`Private`
####Отдает:
<pre>
array(), 
...,
array(
 name,
 id
)
</pre>

##sortMediaByStages
Разбиение массива медиа на подмассивы этапов
####Принимает:
$arMedia - Массив медиа файлов
####Вызывается:
`Private`
####Отдает:
<pre>
OTHER => array(Массив из битрикса, без форматирования)
STAGES => array(Массив из битрикса, без форматирования)
EVENTS => array(Массив из битрикса, без форматирования)
</pre>

##saveAction
Сохранение загруженного файла в инфоблок
####Принимает:
$dzuuid - Временное название файла <br>
$dztotalchunkcount - Количество чанков <br>
$fileName - Настоящее название <br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.save')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors')
</pre>

##saveWithModuleAction
Сохранение загруженного файла в инфоблок (версия для новой модульной структуры)
####Принимает:
$id_parent - ID элемента которому будет принадлежать медиа. Например если type_parent = TRAINING, то id_parent это ID тренировки<br>
$type_parent - Тип куда загружать медиа TRAINING - тренировки<br>
$dzuuid - Временное название файла <br>
$dztotalchunkcount - Количество чанков <br>
$fileName - Настоящее название <br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.saveWithModule')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
</pre>

##saveMediaForEvent
Сохранение загруженного файла в инфоблок  для мероприятия
####Принимает:
$id_task - ид задания<br>
$id_group -ид групы или ид пользователяи<br>
$dzuuid - Временное название файла <br>
$dztotalchunkcount - Количество чанков <br>
$fileName - Настоящее название <br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.saveMediaForEvent')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
</pre>


##delMediaAction
Удаление элемента из инфоблока медиа
####Принимает:
$id_media - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.delMedia')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors')
</pre>

##overWriteAction
Изменение файла, получает formData
####Принимает:
formData через POST, на прямую параметры не принимает
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.overWrite')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors')
</pre>

##cancelEditAction
Вызывается при отмене изменений на странице редактирования медиа
####Принимает:
$id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.cancelEdit')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors')
</pre>

##getMediaDetailAction
Вернет информацию для детальной медиа файла
####Принимает:
$fileId - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.cancelEdit')
</pre>
####Отдает:
<pre>
event => array(
    name - Название файла,
    text - 
),
stage => array(
    name -,
    text -
),
status,
user => array(
    image_id,
    l_name,
    name,
    picture,
    s_name,
    u_name
),
file => <a name="getShortMediaTempData">getShortMediaTempData</a>**
</pre>
** `Массив расширенный, смотреть уже по факту получения`

##getMediaWithModuleAction
Вернет информацию для детальной медиа файла из модульной системы (новая)
####Принимает:
id_media - ID медиа в системе битры
type - тип модуля для возврата данный, например TRAINING = вернет медиа для тренировок
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getMediaWithModule')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>

##getMediaListWithModuleAction
Вернет информацию для альбома медиа из модульной системы (новая)
####Принимает:
id_album - ID альбома. Например для type = TRAINING это будет id регулярной тренировки
type - тип модуля для возврата данный, например TRAINING = вернет медиа для тренировок
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getMediaListWithModule')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>

##getRulesForMediaAction
Получить список прав для данного пользователя к медиа файлу
####Принимает:
$media_id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getRulesForMedia')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array(
    captain => array(**
        canBack: false - Можно ли отозвать из мероприятия
        canDel: true - Можно ли удалить
        canEdit: true - Можно ли редактировать
        canPush: true - Можно ли отправить в мероприятие 
    )
    creator => array(**
        canBack: true - Можно ли отозвать из команды
        canDel: true - Можно ли удалить
        canEdit: false - Можно ли редактировать
        canPush: false - Можно ли отправить в команду
    )
)
</pre>
** `Наличие массива зависит от Вашего положеня в команде, если вы 
отправили в свою команду, то прийдет два массива`

##pushMediaInTeamAction
Отправить медиа в команду
####Принимает:
$media_id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.pushMediaInTeam')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>

##getBackMediaFromTeamAction
Отозвать медиа из команды
####Принимает:
$media_id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getBackMediaFromTeam')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>

##pushMediaInEventAction
Отправить медиа в мероприятие
####Принимает:
$media_id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.pushMediaInEvent')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>

##getBackMediaFromEventAction
Отозвать медиа из мероприятия
####Принимает:
$media_id - ID медиа в системе битры
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.newmedia.getBackMediaFromEvent')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
</pre>