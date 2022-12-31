#Контроллер Training  [назад](index.md)
######/local/modules/git.module/lib/controllers/[training.php](../../lib/controllers/training.php)

##checkModules
подключем необходимые библиотеки для работы с Инфоблоками и Социальными сетями(команды)

##getTrainingsPageAction
Получение списка всех тренировок, страница со списком тренировок + фильтр, контроллер
####Принимает:
arFilter - array c фильтрами:<br>
TRAINER<br>
DAY<br>
LOCATION<br>
WAVE
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getTrainingsPage')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => Массив с данными
status: "success"
</pre>
#### WHAT_CAN - Данные для кнопки у тренировки. Возможные значения:
<pre>
Пользователь:
'READY', // Не записан и могу записаться
'ALREADY', // Уже записан
'MISSED', // Записан но пропустил
'LATE', // Прошла
'COMPLETE', // Участвовал
'BANNED' // Бан, ничего не может

Тренер:
'WAIT_USERS', // Запись открыта
'IS_FULL', // Мест нет
'CLOSE', // Закрыл
'NO_CLOSE' // Не закрыл
</pre>
#### TRAINING_IS_END - Завершенность тренировки. Показывать ли присутствие участников на тренировке
#### REPORT_IS_SUBMIT - Отправлен ли отчет.
#### У каждой тренировки есть участники MEMBERS, у каждого участника статус COMPLETE = Участвовал | MISSED = Записан но пропустил
#### Есть ключ USER - данные активного пользователя
#### Есть ключ LOCK_DATA - Есть ли блокировки. array LEFT = Были блокировки, в значении указано оставшееся количество | DATE = дата разблокировки | EMPTY = Все ок, блокировок не было
#### Есть ключ FILTER - массив с вариантами для заполнения фильтра, есть ключи TRAINER, DAY, LOCATION, WAVE

##getTrainingPageAction
Получение одной тренировки
####Принимает:
id_training - ИД тренировки
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getTrainingPage')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => Массив с данными
status: "success"
</pre>
#### WHAT_CAN - Данные для кнопки у тренировки. Возможные значения:
<pre>
Пользователь:
'READY', // Не записан и могу записаться
'ALREADY', // Уже записан
'MISSED', // Записан но пропустил
'LATE', // Прошла
'COMPLETE', // Участвовал
'BANNED' // Бан, ничего не может

Тренер:
'WAIT_USERS', // Запись открыта
'IS_FULL', // Мест нет
'CLOSE', // Закрыл
'NO_CLOSE' // Не закрыл
</pre>
#### TRAINING_IS_FINISH - Показывать ли кнопку "Редактировать до" для тренера в тренировке
#### TRAINING_IS_END - Завершенность тренировки. Показывать ли присутствие участников на тренировке
#### REPORT_IS_SUBMIT - Отправлен ли отчет.
#### У каждой тренировки есть участники MEMBERS, у каждого участника статус COMPLETE = Участвовал | MISSED = Записан но пропустил
#### Есть ключ USER - данные активного пользователя
#### Есть ключ LOCK_DATA - Есть ли блокировки. array LEFT = Были блокировки, в значении указано оставшееся количество | DATE = дата разблокировки | EMPTY = Все ок, блокировок не было

##setStatusMemberInTrainingAction
Изменит статус участника тренировки
####Принимает:
arMembers - array, каждый элемент массива содержит два ключа: ID - ИД участника, STATUS - Y = был на тренировке, а N = не пришел <br>
id_training - ИД тренировки <br>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.setStatusMemberInTraining')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => ИД пользователя со значением Y
status: "success"
</pre>

##editTrainingAction
Редактирование полей тренировки
####Принимает:
id_training - ИД тренировки <br>
arFields - array Полей:<br>
<pre>
DESCRIPTION - string | Описание
NEED_NOTIFY_MEMBERS - string | Y = Оповестить участников; N = Не оповещать участников
</pre>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.editTraining')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => Y
status: "success"
</pre>

##addMemberInTrainingByTrainerAction
Добавление пользователя в тренировку, метод для тренера (нет проверки на поля)
####Принимает:
id_training - ИД тренировки <br>
id_member - ИД участника
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.addMemberInTrainingByTrainer')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
</pre>

##addMeInTrainingAction
Добавление активного пользователя в тренировку
####Принимает:
id_training - ИД тренировки <br>
id_member - ИД участника
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.addMeInTraining')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: NEED_INFO = Для присоединения нужно заполнить персональную информацию | array Текст ошибки
</pre>
#####NEED_INFO - Данные профиля получать методом
<pre>
BX.ajax.runAction('git:module.api.personal.userProfile')
</pre>

##delMemberInTrainingByTrainerAction
Удаление участника из тренировки тренером
####Принимает:
id_training - ИД тренировки <br>
id_member - ИД участника
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.delMemberInTrainingByTrainer')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
</pre>

##delMeInTrainingByTrainerAction
Удаление себя из тренировки
####Принимает:
id_training - ИД тренировки <br>
id_member - ИД участника
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.delMeInTrainingByTrainer')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
</pre>

##doEndTrainingAction
Завершение тренировки тренером (отправка отчета)
####Принимает:
id_training - ИД тренировки
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.doEndTraining')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors', 'errors_code');
result => SUCCESS = Успех 
status: "success" | "error"
errors: array Текст ошибки
errors_code: array код ошибки. TRAINING_IS_EMPTY = Тренировка не найдена | NO_MEDIA = Медиа не найдено | TRAINING_IS_OPEN = Тренировка еще не прошла
</pre>

##getIsIBlockAction
Получить данные блокировки активного пользователя в тренировках
####Принимает:
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getIsIBlock')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors', 'errors_code');
result => array LEFT = Были блокировки, в значении указано оставшееся количество | DATE = дата разблокировки | EMPTY = Все ок, блокировок не было
status: "success" | "error"
errors: array Текст ошибки
errors_code: array код ошибки. NO_USER = Пользователь не найден
</pre>

##getReportPageAction
Вернет страницу отчета по тренировкам
####Принимает:
arFilter - array, принимает ряд параметров для фильтрации
<pre>
TRAINERS - array тренеров
TRAININGS - array тренировок
MEMBERS - array участников
DATE_FROM - строка даты в формате 27.12.2021
DATE_TO - строка даты в формате 27.12.2021
</pre>
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getReportPage')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => array
status: "success" | "error"
errors: array Текст ошибки
</pre>

##addTrainingInRegularAction
Добавление тренировок в регулярные тренировки. Пока только для админа т.к нет защиты
####Принимает:
id_regular - ИД регулярной тренировки<br>
arTrainings - массив тренировок
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.addTrainingInRegular')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => SUCCESS
status: "success" | "error"
errors: array Текст ошибки
</pre>

##setTrainerAction
Задать тренера для тренировки. Пока только для админа т.к нет защиты
####Принимает:
id_rg_training - ИД регулярной тренировки<br>
id_trainer - ИД тренера
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.setTrainer')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors', 'errors_code');
result => SUCCESS
status: "success" | "error"
errors: array Текст ошибки
</pre>

##getMembersByStrAction
Вернет список всех участников тренировок по строке поиска
####Принимает:
string - string ФИО участника
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getMembersByStr')
</pre>
####Отдает:
<pre>
array('status', 'result');
result => array MEMBERS
status: "success" | "error"
</pre>

##getMembersToTrainingByStrAction
Вернет список людей которые еще не записаны на тренировку
####Принимает:
string - string ФИО участника<br>
id_training - ИД тренировки для которой ищут людей
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getMembersToTrainingByStr')
</pre>
####Отдает:
<pre>
array('status', 'result');
result => array USERS
status: "success" | "error"
</pre>

##createExcelFromListAction
Сформирует xml файл по тренировкам
####Принимает:
arTrainings - array из ИД тренировок
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.createExcelFromList')
</pre>
####Отдает:
<pre>
array('status', 'result', 'errors');
result => путь к файлу
status: "success" | "error"
errors: ошибка
</pre>

##getKuratorAction
Получение данных куратора сайта
####Принимает:
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.getKurator')
</pre>
####Отдает:
<pre>
array('status', 'result');
result => array
status: "success" | "error"
errors: array Текст ошибки
</pre>

##Сохранение файла происходит как и раньше, но в конце вызывается данный метод
###Детальнее в [newmedia-с.php](../../readme/itrack/newmedia-c.md)
<pre>
BX.ajax.runAction('git:module.api.newmedia.saveWithModule')
</pre>
Вместо
<pre>
BX.ajax.runAction('git:module.api.newmedia.save')
</pre>

##addUnregisterMember
Добавить в тренировку пользователя который не зарегистрирован
####Принимает:
id_training - ИД тренировок
arFields - массив полей, а именно:
<pre>
'NAME',
'LAST_NAME',
'SECOND_NAME',
'PERSONAL_BIRTHDAY',
'EMAIL',
'PERSONAL_PHONE',
'UF_DEPARTMENT',
'WORK_POSITION',
'UF_TRAINING_CONF'
</pre>
где UF_TRAINING_CONF должен быть равен Y или булево true
####Вызывается:
<pre>
BX.ajax.runAction('git:module.api.training.addUnregisterMember')
</pre>
####Отдает:
<pre>
array('status', 'result');
result => SUCCESS
status: "success" | "error"
errors: array Текст ошибки
</pre>