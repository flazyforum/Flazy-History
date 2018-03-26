<?php

// Language definitions used in install.php
$lang_install = array(

// Install Form
'Install Flazy'			=>	'Установка Flazy %s',
'Install Flazy decs'		=>	'Три шага на пути в общение.',
'Install intro'			=>	'Для установки Flazy вы должны заполнить форму ниже. Пожалуйста, читайте инструкции и пояснения перед заполнением полей. Если вы столкнулись с трудностями во время установки, пожалуйста, обратитесь к документации, входящей в состав установочного пакета Flazy.',
'Part1'				=>	'Часть 1 — Параметры базы данных',
'Part1 intro'			=>	'Пожалуйста, введите требуемую информацию, для настройки параметров своей БД для Flazy. Вы должны точно узнать и указать всю эту информацию, прежде чем продолжать установку. Пояснения к полям этой формы даны ниже.',
'Database type'			=>	'Тип БД',
'Database name'			=>	'Имя БД',
'Database server'		=>	'Сервер БД',
'Database username'		=>	'Пользователь БД',
'Database password'		=>	'Пароль БД',
'Database user pass'		=>	'Имя пользователя и пароль пользвателя Базы Данных:',
'Table prefix'			=>	'Префикс таблиц',
'Database type info'		=>	'На текущий момент Flazy поддерживает MySQL, PostgreSQL и SQLite. Если ваш тип БД отсутствует в выпадающем меню, это означает, что PHP не поддерживает осбобенности вашей БД. Больше информации по типам БД и их поддержке вы сможете найти в FAQ.',
'Mysql type info'		=>	'Flazy определил, что ваша сборка PHP поддерживает два разных способа связи с MySQL. По способу "<em>стандарт</em>" и "<em>улучшенный</em>". Если вы не уверены, какой из способов выбрать, попробуйте начать с улучшенного, если же он будет работать некорректно - оставьте стандартный.',
'MySQL InnoDB info'		=>	'Flazy определил, что ваш MySQL сервер, возможно, поддерживает <a href="http://dev.mysql.com/doc/refman/5.0/en/innodb-overview.html">InnoDB</a>. Это было бы хорошим выбором, если вы планируете создание большого форума. Если вы не уверенны, не рекомендуется использовать InnoDB.',
'Database server info'		=>	'Введите адрес сервера БД (например: <em>localhost</em>, <em>mysql1.example.ru</em> или <em>208.77.188.166</em>). Вы можете назначить свой номер порта, если ваша БД недоступна по первоначальному номеру (например: <em>localhost:3580</em>). Для SQLite введите что угодно или оставьте \'localhost\'.',
'Database name info'		=>	'Введите имя БД, в связке с которой будет установлен Flazy. Эта БД уже должна существовать. Для SQLite это относительный путь до файла БД. Если файл БД SQLite не существует, Flazy попытается его создать.',
'Database username info'	=>	'Введите имя пользователя и пароль, используемые для связи с выбранной БД. Для SQLite данный параметр не трубуется.',
'Table prefix info'		=>	'Опционально — введите префикс для таблиц БД. Определив префикс вы сможете использовать несколько копий Flazy с одной БД (например: <em>foo_</em>).',
'Part1 legend'			=>	'Информация о базе данных',
'Database type help'		=>	'Выберите тип базы данных.',
'Database server help'		=>	'Адрес сервера БД. Для SQLite введите что угодно.',
'Database name help'		=>	'Существующая база данных, в которую будет установлен Flazy.',
'Database username help'	=>	'Для соединения с базой данных. Для SQLite не требуется.',
'Database password help'	=>	'Для соединения с базой данных. Для SQLite не требуется.',
'Table prefix help'		=>	'Необязательный префикс базы данных, например "foo_".',
'Part2'				=>	'Часть 2 — Настройка Администратора форума',
'Part2 legend'			=>	'Параметры Администратора',
'Part2 intro'			=>	'Пожалуйста, введите требуемую информацию для настройки параметров администратора этого форума. Позже вы сможете создать больше администраторов и модераторов.',
'Admin username'		=>	'Имя Администратора',
'Admin password'		=>	'Пароль Администратора',
'Admin confirm password'	=>	'Подтвердите пароль',
'Admin e-mail'			=>	'E-mail адрес Администратора',
'Username help'			=>	'От 2 до 25 символов.',
'Password help'			=>	'Минимум 4 символа. Чувствительно к регистру.',
'Confirm password help'		=>	'Повторите пароль.',
'E-mail address help'		=>	'Адрес E-mail Администратора.',
'Part3'				=>	'Часть 3 — Настройки форума',
'Part3 legend'			=>	'Информация форума',
'Part3 intro'			=>	'Пожалуйста, введите требуемую информацию. Обратите особое внимание на базовый URL и внимательно читайте пояснения ниже.',
'Board title'			=>	'Заголовок форума',
'Board title and desc'		=>	'Заголовок форума и описание',
'Board description'		=>	'Описание форума',
'Base URL'			=>	'Базовый URL',
'Board title info'		=>	'Введите заголовок и короткое описание вашего форума Flazy. Они будут отображаться вверху каждой страницы. Оставьте поля пустыми, чтобы использовать заголовок и описание по умолчанию. Их можно будет изменить позже.',
'Base URL info'			=>	'Пожалуйста, обратите особое внимание на параметр "базовый URL". Вы должны правильно ввести его иначе ваш форум будет работать некорректно. Бызовый URL - это URL (без закрывающего слеша) вашего форума Flazy (например: <em>http://forum.example.ru</em> или <em>http://example.ru/~myuser</em>). Имейте ввиду, изначально прописанное в этом поле значение - просто догадка Flazy.',
'Base URL help'			=>	'URL (без закрывающего слеша) вашего форума Flazy. Подробнее читайте выше.',
'Start install'			=>	'Начать установку', // Label for submit button
'Required'			=>	'(Обязательно)',
'Required warn'			=>	'Все поля, помеченные %s, должны быть заполнены перед продолжением.',
'Default language'		=>	'Язык по умолчанию',
'Default language help'		=>	'Выбрать язык форума по умолчанию',
'Choose language'		=>	'Выбрать язык',
'Choose language help'		=>	'Выбрать язык, на котором будет происходить установка форума',
'Installer language'		=>	'Язык установки',
'Choose language legend'	=>	'Язык установки',

// Install errors
'No database support'		=>	'Ваша сборка PHP не поддерживает БД, которые поддерживает Flazy. Необходима поддержка хотя бы одной — MySQL, PostgreSQL или SQLite для продолжения установки.',
'Missing database name'		=>	'Вы должны ввести имя БД. Пожалуйста, вернитесь и исправьте ошибку.',
'Username too long'		=>	'Имена пользователей не могут быть длинее 25 символов. Пожалуйста, вернитесь и исправьте ошибку.',
'Username too short'		=>	'Имена пользователей должны иметь длину не менее 2-х символов. Пожалуйста, вернитесь и исправьте ошибку.',
'Pass too short'		=>	'Пароли должны иметь длину не менее 4-х символов. Пожалуйста, вернитесь и исправьте ошибку.',
'Pass not match'		=>	'Пароли не совпадают. Пожалуйста, вернитесь и исправьте ошибку.',
'Username guest'		=>	'Имя пользователя «Гость» зарезервировано. Пожалуйста, вернитесь и исправьте ошибку.',
'Username BBCode'		=>	'Имена пользователей не могут содержать никаких тегов форматирования текста (BB-кодов), которые используются на форуме. Пожалуйста, вернитесь и исправьте ошибку.',
'Username reserved chars'	=>	'Имена пользователей не могут содержать символы \', " и [ или ] одновременно. Пожалуйста, вернитесь и исправьте ошибку.',
'Username IP'			=>	'Имена пользователей не могут быть записаны в форме IP адреса. Пожалуйста, вернитесь и исправьте ошибку.',
'Invalid email'			=>	'Введенный e-mail адрес Администратора не верен. Пожалуйста, вернитесь и исправьте ошибку.',
'Missing base url'		=>	'Вы должны указать базовый URL. Пожалуйста, вернитесь и исправьте ошибку.',
'No such database type'		=>	'\'%s\' не верный тип БД.',

'Invalid MySQL version'		=>	'Ваша версия MySQL - %1$s. Минимальные требования для корректной работы Flazy - MySQL %2$s. Вы должны обновить MySQL прежде, чем продолжать установку.',
'Invalid table prefix'		=>	'Префикс \'%s\' содержит недопустимые символы или слишком длинный. Префикс может содержать буквы от a до z, любые цифры и символ подчеркивания. Однако, он не должен начинаться с цифры. Максимальная длина — 40 символов. Пожалуйста, укажите другой префикс.',
'SQLite prefix collision'	=>	'Префикс \'sqlite_\' зарезервирован для использования ядром SQLite. Пожалуйста, укажите другой префикс.',
'Flazy already installed'	=>	'Таблица "%1$susers" уже существует в БД "%2$s". Это может означать, что Flazy уже установлен или установленно какое-то другое ПО, использующее одну или несколько таблиц, необходимых для работы Flazy. Если вы хотите установить несколько копий Flazy в одну БД, вы должны указать другой префикс.',
'InnoDB not enabled'		=>	'InnoDB не включена. Пожалуйста, выберите БД, которая не поддерживает InnoDB, или включите InnoDB в настройках вашего MySQL-сервера.',
'Invalid language'		=>	'Языковой пакет который вы выбрали не существует или повреждён. Уточните и попробуйте еще раз.',

// Used in the install
'Default announce heading'	=>	'Пример объявления',
'Default announce message'	=>	'<p>Введите текст вашего объявления здесь.</p>',
'Default HTML message'		=>	'Введите (x)HMTL код',
'Default Adbox message'		=>	'<p>Введите здесь текст. Это сообщение увидят все посетители</p>',
'Default Guestbox message'	=>	'<p>Если вы хотите получить доступ к всем разделам форума, необходимо <a href="login.php">войти</a> или <a href="register.php">зарегистрироваться</a></p>',
'Default maint message'		=>	"На форуме ведутся профилактические работы. Соблюдайте спокойствие. В ближайшее время форум возобновит свою работу. Спасибо за понимание!<br />\\n<br />\\n/Администратор",
'Default rules'			=>	'Введите правила здесь.',
'Default category name'		=>	'Тестовая категория',
'Default forum name'		=>	'Тестовый форум Flazy',
'Default forum descrip'		=>	'Это просто тестовый форум.',
'Default topic subject'		=>	'Тестовое сообщение Flazy',
'Default post contents'		=>	'Если вы видите это сообщение, значит Ваш [b]Flazy[/b] форум был установлен правильно и полностью готов к работе. Осталось только зайти в админ-панель и настроить его на ваш вкус. Приятного пользования.',
'Default rank 1'		=>	'Новый участник',
'Default rank 2'		=>	'Участник',

// Installation completed form
'Success description'		=>	'Поздравляем! Flazy %s был успешно установлен.',
'Success welcome'		=>	'Пожалуйста, следуйте инструкциям написаным ниже, чтобы закончить установку.',
'Final instructions'		=>	'Последние инструкции',
'No write info 1'		=>	'<strong>Важно!</strong> Для завершения установки вам необходимо нажать на кнопку, расположенную ниже, чтобы скачать файл под именем config.php. Затем вам нужно загрузить этот файл в директорию <em>/include</em> вашего Flazy форума.',
'No write info 2'		=>	'Как только вы загрузите в корневую директорию файл config.php, Flazy будет полностью установлен! Как только файл будет загружен, вы можете перейти %s.',
'Go to index'			=>	'перейти к главной странице форума',
'Warning'			=>	'<strong>Внимание!</strong>',
'No cache write'		=>	'<strong>Каталог cache не доступен для записи!</strong> Для корректной работы Flazy, каталог под названием <em>cache</em> должен быть доступен для записи. Используйте chmod чтобы задать права доступа для каталога. Если сомневаетесь, установите chmod на 0777.',
'No avatar write'		=>	'<strong>Каталог avatar не доступен для записи!</strong> Если вы хотите, чтобы пользователи могли загружать собственные аватары, вы должны убедиться, что каталог под названием <em>img/avatars</em> доступен для записи. Позже вы сможете указать другую папку для хранения аватар (смотрите Администрирование/Настройки/Особенности). Используйте chmod чтобы задать права доступа для каталога. Если сомневаетесь, установите chmod на 0777.',
'File upload alert'		=>	'<strong>Загрузка файлов не разрешена на этом сервере!</strong> Если вы хотите, чтобы пользователи могли загружать собственные аватары вы должны включить параметр file_uploads в настройках PHP. Как только загрузка файлов будет разрешена, загрузка аватаров может быть включена в настройках Администрирование/Настройки/Особенности.',
'Download config'		=>	'Скачать файл config.php', // Label for submit button
'Write info'			=>	'Flazy был полностью установлен! Теперь вы можете %s.',
);