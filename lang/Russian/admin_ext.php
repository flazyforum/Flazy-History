<?php

// Языковые конструкции используемые на странице расширений
$lang_admin_ext = array(

'Install extension'		=>	'Установить расширение',
'Upgrade extension'		=>	'Обновить расширение',
'Extensions available'		=>	'Расширения доступные для установки',
'Rep extensions available'	=>	'Расширения доступные для установки из репозитория Flazy',
'Hotfixes available'		=>	'Исправления доступные для установки',
'Installed extensions'		=>	'Установленные расширения',
'Version'			=>	'Версия %s.',
'Hotfix'			=>	'Исправление',
'Installed hotfixes'		=>	'Установить исправления',
'Installed extensions warn'	=>	'<strong>Внимание!</strong> При удалении расширения все данные, связанные с ним, также навсегда будут удалены из базы данных, и их нельзя будет восстановить путем переустановки расширения. Если вы хотите сохранить эти данные, то просто отключите его.',
'Uninstall extension'		=>	'Удалить расширение',
'Uninstall hotfix'		=>	'Удалить исправление',
'Uninstall'			=>	'Удалить',
'Uninstall extension confirm'	=>	'Вы уверены, что хотите удалить расширение «%s»?',
'Enable'			=>	'Включить',
'Disable'			=>	'Отключить',
'Upgrade hotfix'		=>	'Обновить исправление',
'Extension error'		=>	'Ошибка',
'Extension loading error'	=>	'Загрузка расширения «%s» завершилась неудачей.',
'Illegal ID'			=>	'ID может содержать только буквенно-числовые символы в нижнем регистре (a—z и 0—9) и символы нижнего подчеркивания (_).',
'Maxtestedon warning'		=>	'Это расширение не было официально протестировано с вашей версией Flazy. Оно может работать некорректно.',
'Missing manifest'		=>	'Отсутствует файл manifest.xml.',
'Failed parse manifest'		=>	'Не удалось обработать manifest.xml.',
'extension root error'		=>	'Корневой элемент расширения неправильный или отсутствует.',
'extension/engine error'	=>	'Корневой элемент скрипта неправильный или отсутствует.',
'extension/engine error2'	=>	'Версия расширения не поддерживается.',
'extension/id error'		=>	'Элемент extension/id неправильный или отсутствует.',
'extension/id error2'		=>	'Не правильное имя папки элемента extension/id.',
'extension/title error'		=>	'Элемент extension/title неправильный или отсутствует.',
'extension/version error'	=>	'Элемент extension/version неправильный или отсутствует.',
'extension/description error'	=>	'Элемент extension/description неправильный или отсутствует.',
'extension/author error'	=>	'Элемент extension/author неправильный или отсутствует.',
'extension/minversion error'	=>	'Элемент extension/minversion неправильный или отсутствует.',
'extension/minversion error2'	=>	'Это расширение требует версию Flazy %s или выше.',
'extension/maxtestedon error'	=>	'Не удается найти или отсутствует директория extension/maxtestedon.',
'extension/note error'		=>	'Неверный элемент extension/note.',
'extension/note error2'		=>	'У элемента extension/note не удается найти или отсутствует атрибут «type».',
'extension/hooks/hook error'	=>	'Элемент extension/hooks/hook неправильный или отсутствует.',
'extension/hooks/hook error2'	=>	'Элемент extension/hooks/hook отсутствует атрибут «id».',
'extension/hooks/hook error3'	=>	'Элемент extension/hooks/hook имеет некорректное значение в атрибуте «priority».',
'extension/hooks/hook error4'	=>	'Элемент extension/hooks/hook имеет содержимое, которое невозможно завершить в среде PHP.',
'No XML support'		=>	'PHP не имеет встроенной поддержки XML. Поддержка XML необходима, если вы хотите использовать расширения Flazy. Ознакомьтесь с документацией PHP, чтобы сконфигурировать его, согласно требованиям.',
'No installed extensions'	=>	'Нет установленных расширений.',
'No installed hotfixes'		=>	'Нет установленных исправлений.',
'No available extensions'	=>	'Нет расширений доступных для установки или обновления.',
'No available hotfixes'		=>	'Нет исправдений, доступных для установки или обновления.',
'Invalid extensions'		=>	'<strong>Важно!</strong> Расширения, перечисленные ниже, были найдены, но они недоступны для установки или обновления, из-за обнаруженных ошибок, перечисленных ниже.',
'Extension installed'		=>	'Расширение установлено.',
'Hotfix installed'		=>	'Исправление установлено.',
'Extension installed info'	=>	'Расширение было установлено, но были обнаружены следующие ошибки.',
'Extension uninstalled'		=>	'Расширение удалено.',
'Hotfix uninstalled'		=>	'Исправление удалено.',
'Extension uninstalled info'	=>	'Расширение было удалено, но были обнаружены следующие ошибки.',
'Install note'			=>	'Инструкции по установке',
'Uninstall note'		=>	'Инструкции по удалению',
'Hotfix download failed'	=>	'Скачивание и установка исправления завершилось неудачей. Пожалуйста, подождите немного и попробуйте снова.',
'Extension disabled'		=>	'Расширение недоступно.',
'Extension enabled'		=>	'Расширение доступно.',
'Hotfix disabled'		=>	'Исправление недоступно.',
'Hotfix enabled'		=>	'Исправление доступно.',
'Extension by'			=>	'Разработчик — %s.',
'Hotfix description'		=>	'Это исправление Flazy было обнаружено службой автоматических обновлений.',
'Repository description'	=>	'Это расширение было обнаружено в репозитории Flazy.',
'Install hotfix'		=>	'Установить исправление',
'Missing dependency'		=>	'Это расширение не может быть установлено до тех пор, пока "%s" установлено и включено',
'Uninstall dependency'		=>	'Это расширение не может быть удалено, пока «%s» установлено.',
'Disable dependency'		=>	'Это расширение не может быть отключено, пока «%s» включено.',
'Disabled dependency'		=>	'Это расширение не может быть включено, пока «%s» отключено.'

);