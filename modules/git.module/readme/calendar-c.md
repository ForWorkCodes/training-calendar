#Контроллер Calendar  [назад](index.md)
######/local/modules/git.module/lib/controllers/[calendar.php](../../lib/controllers/calendar.php)

##initCalendarAction
Инициализация данных для фильтров и др
####Принимает:
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.initCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array. PLACE - Место | DIRECTIONS - Направление | DAY - Дени недели | TIMES - Время | ACTIVE - Черновик или Паблик | SHOW_CREATE_BTN - Показывать ли кнопку "создать событие"
status: "success" | "error"
</pre>

##getCalendarAction
Получить события календаря
####Принимает:
arFilter - array Полей (может быть пустым):<br>
<pre>
DATE - Выбранный день в формате: 30.12.2022
DATE_FROM и DATE_TO - Парные. Время события от и до, например первый и последний день страницы календаря. Формат: 30.12.2022
IMPORTANT - Только важные события. Формат: Y
PLACE - Место. Формат: array(id, id, id ...). Приходит из initCalendarAction
TIME - Время события. Формат: array(id, id, id ...). MORNING | DINNER |LUNCH. Приходит из initCalendarAction
DAYS - День события. Формат: array(id, id, id ...). 1 - Вс. Приходит из initCalendarAction
SEARCH - Искомая фраза. Текст без форматирования
DIRECTIONS - Направление (поднаправление). Формат: array(id, id, id ...). Приходит из initCalendarAction
ACTIVE - Черновик. Y - доступен всем (не черновик) | N - Доступен модератору (черновик)
ACTUAL - Актуальность. Для карточек. Y - Только актуальные (по умолчанию). N - прошедшие
</pre>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.getCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array
status: "success" | "error"
</pre>

##initPageCreateCalendar
Получение массива для страницы создания/редактирования события
####Принимает:
idCalendar - ID события или ничего для первого создания события
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.initPageCreateCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array. Помимо прочего может вернуть CAN_SET_IMPORTANT, если Y то можно установить важность события
status: "success" | "error"
</pre>

##addCalendarAction
Добавить событие календаря
####Принимает:
js ФормДата типа:
<pre>var formData = new FormData();</pre>
Набор полей:<br>
<pre>
ACTIVE = Y - Опубликовано | N - Черновик
NAME - Название события
PREVIEW_PICTURE - Аватар события
DETAIL_PICTURE - Обложка видео
PREVIEW_TEXT - Краткое описание
DETAIL_TEXT - Полное описание
DIRECTIONS = ID - Направление. Получить в initPageCreateCalendar
IMPORTANT = Y - Важное. Получить в initPageCreateCalendar
PLACE = ID - Место. Получить в initPageCreateCalendar
ADDRESS - Адрес
VIDEO = ID - Видео. Получить загрузив стандартным методом через newmedia::uploadfile -> newmedia::save
TYPE = ID - Тип события единичное или продолжительное. Получить в initPageCreateCalendar
DATE_FROM = Дата начала события (продолжительное) | Дата события (единичное). В формате dd.mm.YYYY
DATE_TO = Дата окончания события (продолжительное). В формате dd.mm.YYYY
NOT_SHOW_TIME - Не показывать время (по умолчанию активен если время не заполнено)
TIME_FROM - Время начала события. В формате чч:мм
TIME_TO - Время окончания события. В формате чч:мм
DATE_TYPE = ID - Дни события. Получить в initPageCreateCalendar
PERSON_ID = Ответственное лицо: ID пользователя
PERSON_NAME = Имя ответственного лица
PERSON_PICTURE = Картинка ответственного лица
PERSON_ROLE = Роль ответственного лица
PERSON_EMAIL = Email ответственного лица
PERSON_PHONE = Телефон ответственного лица
</pre>
[newmedia::uploadfile -> newmedia::save](newmedia-c.md)

Получение отдельных полей пользователя можно методом [getUserField](puser.md)
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.addCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => ID
status: "success" | "error"
</pre>

##updateCalendarAction
Редактировать событие календаря
####Принимает:
arFields - array Полей:<br>
<pre>
в дополнение к полям создания еще нужно передавать ID события которое пришло на редактирование
</pre>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.updateCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array
status: "success" | "error"
</pre>

##delCalendarAction
Удалить событие календаря
####Принимает:
id - id события<br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.delCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => Y
status: "success" | "error"
</pre>

##downloadCalendarAction
Скачать событие календаря
####Принимает:
id - id события<br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.calendar.downloadCalendar')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => Содержимое файла. Далее нужно на js сгенерировать файл для скачивания
status: "success" | "error"
</pre>
Пример создания файла на js:
<pre>
if (res.data.status == 'success') {
    let text = res.data.result;
    downloadAsFile(text);
    function downloadAsFile(data) {
        let a = document.createElement("a");
        let file = new Blob([data], {type: 'text/calendar'});
        a.href = URL.createObjectURL(file);
        a.download = "calendar.ics";
        a.click();
    }
}
</pre>