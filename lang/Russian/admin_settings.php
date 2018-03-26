<?php

$lang_admin_settings = array(

'Settings updated'		=>	'Настройки обновлены.',

// Setup section
'Setup personal'		=>	'Придайте индивидуальность вашему Flazy форуму',
'Setup personal legend'		=>	'Установка Flazy',
'Board description label'	=>	'Описание форума',
'Board title label'		=>	'Название форума',
'Default style label'		=>	'Стиль по умолчанию',
'Forced style'			=>	'Принудительный стиль',
'Forced style help'		=>	'Этот стиль будет отображаться у всех участников не зависимо от их выбора',
'User style'			=>	'Стиль участника',
'User style help'		=>	'Разрешить участникам выбирать свой стиль оформления форума',
'Select'			=>	'Выберите',
'Setup local'			=>	'Настройте Flazy в зависимости от вашего местоположения',
'Setup local legend'		=>	'Местные настройки',
'Default timezone label'	=>	'Часовой пояс',
'Adjust for DST'		=>	'Летнее время',
'DST label'			=>	'Автоматический переход на летнее время и обратно (сезонный перевод часов на 1 час)',
'Default language label'	=>	'Язык по умолчанию',
'Default language help'		=>	'Если вы добавили или удалили языковой пакет, а его не видно, эту страницу нужно обновить',
'Forced language'		=>	'Принудительный язык',
'Forced language help'		=>	'Этот язык будет у всех участников не зависимо от их выбора',
'Time format label'		=>	'Формат времени',
'Date format label'		=>	'Формат даты',
'Current format'		=>	'[ Текущий формат: %s ] %s',
'External format help'		=>	'Смотрите <a class="exthelp" href="http://www.php.net/manual/ru/function.date.php">здесь</a> для настройки параметров.',
'Setup timeouts'		=>	'Настройки таймаута и времени задержек',
'Setup timeouts legend'		=>	'Таймайт по умолчанию',
'Visit timeout label'		=>	'Таймаут визитов',
'Visit timeout help'		=>	'Время с последнего посещения, через которое данные обновятся вновь (в секундах)',
'Online timeout label'		=>	'Онлайн таймаут',
'Online timeout help'		=>	'Время бездействия, через которое вы будете удалены из списка присутствующих на форуме (в секундах)',
'Redirect time label'		=>	'Время переадресации',
'Redirect time help'		=>	'Если указан 0, страница переадресации не будет показана (не рекомендуется)',
'Setup pagination'		=>	'Количество тем и сообщений по умолчанию, отображаемых на одной странице',
'Setup pagination legend'	=>	'Нумерация страниц по умолчанию',
'Topics per page label'		=>	'Тем на странице',
'Posts per page label'		=>	'Собощений на странице',
'Topic review label'		=>	'Обзор темы',
'Topic review help'		=>	'Новые первыми. Введите 0 чтобы отключить.',
'Setup reports'			=>	'Включите и выбертите метод отправки жалоб администрации',
'Used reporting'		=>	'Использовать систему жалоб',
'Report enabled'		=>	'Включить жалобы',
'Report enabled help'		=>	'Разрешить участникам оставлять жалобы на сообщения.',
'Setup reports legend'		=>	'Получать сообщения',
'Reporting method'		=>	'Метод оповещения',
'Report internal label'		=>	'С помощью внутренней системы оповещений.',
'Report both label'		=>	'Оба метода - с помощью внутренней системы оповещений и по e-mail, на адреса в списке рассылки.',
'Report email label'		=>	'По e-mail, на адреса в списке рассылки.',
'Setup URL'			=>	'Настроить ЧПУ (<abbr title ="Search Engine Friendly">SEF</abbr> URLs) для страниц форума',
'Setup URL legend'		=>	'Выберите схему',
'URL scheme info'		=>	'<strong>ВНИМАНИЕ!</strong> Для работы URL-схем вам необходимо скопировать/загрузить файл .htaccess из директории extras в корневую директорию форума. Кроме того, сервер, на котором расположен ваш форум, должен быть сконфигурирован с поддержкой mod_rewrite и должен разрешать использование файлов .htaccess. Для серверов, кроме Apache, обратитесь к документации вашего сервера.',
'URL scheme label'		=>	'URL-схема',
'URL scheme help'		=>	'Убедитесь, что вы прочли и поняли информацию, написанную выше',
'Setup links'			=>	'Добавить свои ссылки в главное меню',
'Setup links info'		=>	'Вводите HTML-код гиперссылок в этом поле. В главное меню может быть добавлено любое количество пунктов. Формат записи добавления новых пунктов такой: X = &lt;a href="URL"&gt;&lt;span&gt;ССЫЛКА&lt;/span&gt;&lt;/a&gt; где X — позиция, на которую ссылка должна быть помещена (например 0 вставит новый пункт в самом начале, а 2 вставит новый пункт после пункта «Пользователи»). Каждый новый пункт пишите с новой строки.',
'Setup links legend'		=>	'Пункты меню',
'Enter links label'		=>	'Введите ваши сслыки',
'Error no board title'		=>	'Вы должны ввести название форума.',
'Error timeout value'		=>	'Значение «Онлайн таймаут» должно быть меньше величины «Таймаут визитов»',

// Features section
'Features general'		=>	'Общие особенности Flazy, которые являются дополнительными настройками',
'Features general legend'	=>	'Общие особенности',
'Searching'			=>	'Поиск',
'Search all label'		=>	'Разрешить участникам использовать поиск во всех разделах.',
'Load server'			=>	'(Отключите, если нагрузка на сервер слишком высокая или ваш форум очень загружен.)',
'User ranks'			=>	'Ранги участников',
'User ranks label'		=>	'Включить ранги участников основаные на колличестве сообщений.',
'Censor words'			=>	'Цензура',
'Censor words label'		=>	'Включить цензуру на определенные слова.',
'Quick jump'			=>	'Меню быстрого перехода',
'Quick jump label'		=>	'Включить выпадающий список быстрого перехода (переход к разделу).',
'Show version'			=>	'Показывать версию форума',
'Show version label'		=>	'Показывать номер версии Flazy внизу страницы.',
'Online list'			=>	'Список активности',
'Users online label'		=>	'Отображать список гостей и зарегистрированных участников, находящихся на форуме.',
'Today online list'		=>	'Сегодня были',
'Users today online label'	=>	'Отображать список участников которые посетили форум в течении суток.',
'Record list'			=>	'Рекорд пользователей',
'Record label'			=>	'Отображать список рекорда пользователей на главной странице.',
'Stats list'			=>	'Статистика',
'Stats label'			=>	'Отображать ссылки на статистику на главной странице и разрешить участникам её просматривать.',
'Online ft list'		=>	'Форум\тему просматривают',
'Online ft label'		=>	'Показывать список участников которые просматривают форум\тему в данный момент.',
'Features posting'		=>	'Особенности тем и сообщений, а также информации об участнике',
'Features posting legend'	=>	'Возможности сообщений',
'Quick post'			=>	'Быстрый ответ',
'Quick post label'		=>	'Включить форму быстрого ответа внизу темы.',
'Subscriptions'			=>	'Подписки',
'Subscriptions label'		=>	'Разрешить участникам подписываться на темы (получать e-mail, когда кто-нибудь отвечает в теме).',
'Guest posting'			=>	'Сообщения гостей',
'Guest posting label'		=>	'Гости должны обязательно вводить e-mail адрес, оставляя сообщение.',
'User has posted'		=>	'Ответы участника',
'User has posted label'		=>	'Отображать точку перед индикатором состояния темы, если участник отвечал в ней ранее.',
'Topic views'			=>	'Количество просмотров темы',
'Topic views label'		=>	'Отслеживать количество просмотров темы.',
'User post count'		=>	'Счетчик сообщений',
'User post count label'		=>	'Показывать счетчик сообщений участника в сообщениях, в профиле и в списке участников.',
'User info'			=>	'Информация об участнике',
'User info label'		=>	'Отображать местонахождение, дату регистрации, колличество сообщений, адреса e-mail, вебсайт, контакты участников под сообщениями.',
'Ua info'			=>	'Показывать User-Agent',
'Ua info label'			=>	'Отображать иконки браузера и операционой системы участника в информации  в сообщениях и профиле.',
'Merge info'			=>	'Объединение сообщений',
'Merge info label'		=>	'Время в секундах в течении которого последующие сообщения от одного участника будут объединяться. Поставте 0 чтобы отключить.',
'Enable bb panel'		=>	'Панель ББ-кодов',
'Enable bb panel label'		=>	'Отображать банель ББ-кодов над формой ввода сообщения.',
'BB panel smilies'		=>	'Колличество смайлов',
'BB panel smilies label'	=>	'Колличество смайлов на панеле ББ-кодов.',
'Features posts'		=>	'Тема и содержание сообщения',
'Features posts legend'		=>	'Настройки темы и содержания сообщений',
'Post content group'		=>	'Настройки сообщений',
'Allow BBCode label'		=>	'Разрешить BB-коды в сообщениях (рекомендуется)',
'Allow img label'		=>	'Разрешить тег (BB-код ) [img] в сообщениях.',
'Smilies in posts label'	=>	'Преобразовывать текстовые смайлы в графические в сообщениях.',
'Make clickable links label'	=>	'Преобразовывать URL-адреса в гиперссылки в сообщениях.',
'Post period label'		=>	'Время редактирования',
'Post period help'		=>	'Время в секундах для редактирования сообщения, без отображения «Отредактировано...».',
'Allow capitals group'		=>	'Заглавные буквы',
'All caps message label'	=>	'Разрешить сообщения, которые содержат только заглавные буквы.',
'All caps subject label'	=>	'Разрешить темы, которые содержат только заглавные буквы.',
'Indent size label'		=>	'Отступ тега [code]',
'Indent size help'		=>	'Количество пробелов для отступа. Если в значении 8, будет обычный отступ, как у других блоков.',
'Quote depth label'		=>	'Глубина цитирования',
'Quote depth help'		=>	'Максимальное количество вложений тега [quote], любое количество вложений, свыше указанного, будет отклонено.',
'Features per'			=>	'Репутация',
'Features per legend'		=>	'Настройки подписей',
'Allow rep'			=>	'Разрешить репутацию',
'Allow rep label'		=>	'Разрешить участникам менять репутацию.',
'Reputation timeout'		=>	'Таймаут репутации',
'Reputation timeout help'	=>	'Время в минута через которое участник может проголосовать снова.',
'Features sigs'			=>	'Подписи участников и их содержание',
'Features sigs legend'		=>	'Настройки подписей',
'Allow signatures'		=>	'Разрешить подписи',
'Allow signatures label'	=>	'Разрешить подписи участников под сообщениями.',
'Signature content group'	=>	'Содержание подписи',
'BBCode in sigs label'		=>	'Разрешить BB-код в подписях.',
'Img in sigs label'		=>	'Разрешить тег (BB-код ) [img] в подписях (не рекомендуется).',
'All caps sigs label'		=>	'Разрешить все заглавные буквы в подписях.',
'Smilies in sigs label'		=>	'Преобразовывать текстовые смайлы в графические в подписях.',
'Max sig length label'		=>	'Максимум символов',
'Max sig lines label'		=>	'Максисмум строк',
'Features Avatars'		=>	'Аватары участников (параметры загрузки и его размеры)',
'Features Avatars legend'	=>	'Настройки аватаров участников',
'Allow avatars'			=>	'Разрешить аватары',
'Allow avatars label'		=>	'Разрешить участникам загружать аватары для отображения в сообщениях.',
'Avatar directory label'	=>	'Директория для загрузки аватаров',
'Avatar directory help'		=>	'Относительно корневой директории Flazy. Для этой директории должны быть установлены права на запись для PHP.',
'Avatar Max width label'	=>	'Максимальная ширина',
'Avatar Max width help'		=>	'Пикселей (рекомендуется 120).',
'Avatar Max height label'	=>	'Максимальная высота',
'Avatar Max height help'	=>	'Пикселей (рекомендуется 120).',
'Avatar Max size label'		=>	'Максимальный размер',
'Avatar Max size help'		=>	'Байт (рекомендуется 20480).',
'Settings for polls'		=>	'Настройка голосования',
'Disable revoting'		=>	'Переголосование',
'Disable revoting info'		=>	'Разрешить участникам изменять свой голос.',
'Disable see results'		=>	'Просмотр результатов',
'Disable see results info'	=>	'Участники могут видеть результаты опроса без голосования.',
'Maximum answers info'		=>	'Максимум вариантов ответов в голосовании (2-100).',
'Maximum answers'		=>	'Максимум ответов',
'Poll min posts'		=>	'Минимум сообщений',
'Poll min posts info'		=>	'Минимум сообщений для голосования',
'Features title'		=>	'Личные сообщения',
'Inbox limit'			=>	'Ограничение входящих',
'Inbox limit info'		=>	'Максимальное колличество входящих сообщениц. 0 — без ограничений.',
'Outbox limit'			=>	'Ограничение исходящих',
'Outbox limit info'		=>	'Максимальное колличество исходящих сообщениц. 0 — без ограничений.',
'Navigation links'		=>	'Ссылка в меню',
'Snow new count'		=>	'Показать «Новые сообщения (Ч)» в верхней части каждой страницы.',
'Show global link'		=>	'Показывать ссылку на страницу личные сообщения в главное меню',
'Google Analytics'		=>	'Добавить Google Analytics',
'Tracker'			=>	'ID в Google Analytics',
'Tracker help'			=>	'Ваш ID в Google Analytics (пример UA-6488859-1). Оставте пыстым если не хотите использовать.',	
'Type'				=>	'Код отслеживания',
'Type old'			=>	'Старый код (urchin.js)',
'Type new'			=>	'Новый код (ga.js)',
'Features update'		=>	'Проверять автоматически наличие обновлений',
'Features update info'		=>	'Если включена автоматическая проверка обновлений, Flazy будет периодически проверять, есть ли новые важные обновления. Включает в себя как релизы новых версий, так и хотфиксы расширений.',
'Features update disabled info'	=>	'Возможность автоматической проверки обновлений отключена. Для поддержки этой функции, PHP вашего сервера должен поддерживать <a href="http://www.php.net/manual/ru/ref.curl.php">cURL extension</a> и <a href="http://www.php.net/manual/ru/function.fsockopen.php">fsockopen() function</a> или быть сконфигурирован с поддержкой <a href="http://www.php.net/manual/ru/ref.filesystem.php#ini.allow-url-fopen">allow_url_fopen</a>.',
'Features update legend'	=>	'Автаматическое обновление',
'Update check'			=>	'Проверять обновления',
'Update check label'		=>	'Включить автоматическую проверку обновлений.',
'Features gzip'			=>	'Выполнять сжатия используя gzip',
'Features gzip legend'		=>	'Результат сжатия',
'Features gzip info'		=>	'Если включено, Flazy будет передавать вашему браузеру данные, сжатые gzip. Это сократит расход трафика, но немного увеличит нагрузку на процессор (CPU). Эта функция требует, чтобы в PHP был сконфигурирован zlib (--with-zlib). Внимание: Если Вы используете один из модулей Apache, таких как mod_gzip или mod_deflate, настроенных на сжатие PHP-скриптов, вам следует отключить эту функцию здесь.',
'Enable gzip'			=>	'Включить gzip',
'Enable gzip label'		=>	'Включить сжатие исходящих данных, используя gzip.',

// Announcements section
'Announcements head'		=>	'Объявление — будет отображаться на всех страницах вашего форума',
'Announcements legend'		=>	'Объявление',
'Enable announcement'		=>	'Отображать объявление',
'Enable announcement label'	=>	'Отображать сообщение объявления.',
'Announcement heading label'	=>	'Заголовок объявления',
'Announcement message label'	=>	'Текст объявления',
'Announcement message help'	=>	'Вы можете использовать HTML в вашем сообщении. Объявления не обрабатываются как сообщения форума',
'Announcement message default'	=>	'<p>Введите ваше объявление сюда.</p>',
'HTML legend'			=>	'HTML коды',
'HTML head'			=>	'HTML коды — будут отображаться на всех страницах вашего форума',
'Enable HTML top'		=>	'Отображать HTML верх',
'HTML label'			=>	'Показывать HTML коды',
'HTML top part'			=>	'HTML верх',
'HTML top help'			=>	'Сообщение будет отображаться в шапке, на самом верху форума. <p>Вы можете использовать HTML в вашем сообщении. Предайте шапке форума индивидуальность.</p>',
'Enable HTML bottom'		=>	'Отображать HTML низ',
'HTML bottom part'		=>	'HTML низ',
'HTML bottom help'		=>	'Сообщение будет отображаться в самом низу форума. <p>Вы можете использовать. HTML в вашем сообщении. Подойдет для вставки кодов кнопок и баннеров.</p>',
'HTML message default'		=>	'Введите HMTL код',
'Adbox legend'			=>	'HTML коды',
'Ad head'			=>	'Рекламные сообщения — будут отображаться на всех страницах вашего форума',
'Enable Adbox'			=>	'Рекламное сообщение',
'Adbox label'			=>	'Показывать HTML коды',
'Adbox part'			=>	'Текст сообщения',
'Adbox help'			=>	'Сообщение будет отображаться на всех страницах вашего форума. <p>Вы можете использовать. HTML в вашем сообщении. Идеально подходит для вставки рекламных объявлений, но может использвоаться и в любой другой сфере.</p>',
'Adbox message default'		=>	'<p>Введите здесь текст. Это сообщение увидят все посетители</p>',
'Enable Guestbox'		=>	'Гостевое сообщение',
'Guestbox help'			=>	'Сообщение будет отображаться только для гостей на всех страницах вашего форума. <p>Вы можете использовать HTML в вашем сообщении. Идеально подходит для вставки рекламных объявлений, но может использоваться и в любой другой сфере.</p>',
'Guestbox message default'	=>	'<p>Если вы хотите получить доступ к всем разделам форума, необходимо <a href="login.php">войти</a> или <a href="register.php">зарегистрироваться</a></p>',
'Enable Topicbox'		=>	'Блок под сообщением',
'Topic legend'		=>	'Число обозначает после какого сообщения в теме будет отображаться блок. Поставте 0 если хотите его выключить.',
'Topicbox help'			=>	'Сообщение будет отображаться под сообщением в теме. <p>Вы можете использовать HTML в вашем сообщении. Идеально подходит для вставки рекламных объявлений, но может использоваться и в любой другой сфере.</p>',

// Registration section
'Registration new'		=>	'Новые регистрации',
'New reg info'			=>	'Регистрация новых участников',
'New reg info'			=>	'Вы можете выбрать, требовать ли от пользователей подтверждения регистрации по e-mail. Когда подтверждение регистрации включено пользователи получают письмо с кодом активации на свой e-mail после процедуры регистрации. По e-mail они также смогут восстанавливать пароли. Если пользователь захочет изменить e-mail после регистрации, то ему также придется отдельно подтвердить это, перейдя по ссылке активации в письме. Это эффективный метод для пресечения регистраций роботов, а также эффективный метод стимуляции пользователей указывать настоящий e-mail при регистрации.',
'Registration new legend'	=>	'Настройки новых регистраций',
'Allow new reg'			=>	'Новые регистрации',
'Allow new reg label'		=>	'Разрешить регистрацию новых участников. Отключайте только по особым обстоятельствам.',
'Verify reg'			=>	'Подтверждать регистрации',
'Verify reg label'		=>	'Требовать подтверждения от всех вновь зарегистрированных участников по e-mail.',
'Reg e-mail group'		=>	'E-mail адрес регистрации',
'Allow banned label'		=>	'Разрешить регистрацию с адресом e-mail, который находится в черном списке (забанен).',
'Allow dupe label'		=>	'Разрешить регистрацию с адресом e-mail, который уже принадлежит другому участнику.',
'Report new reg'		=>	'Уведомлять по e-mail',
'Report new reg label'		=>	'Уведомлять лиц в списке рассылки о регистрации новых участников на форуме.',
'E-mail setting group'		=>	'Базовые настройки e-mail',
'Display e-mail label'		=>	'Показывать e-mail адрес другим участникам.',
'Allow form e-mail label'	=>	'Скрывать e-mail адрес, но разрешить отправлять e-mail сообщения через форум.',
'Disallow form e-mail label'	=>	'Скрывать e-mail адрес и запретить отправлять e-mail сообщения через форум.',
'Registration timeout'		=>	'Время регистрации',
'Registration timeout help'	=>	'Позволяет устанавливать перерыв между регистрацией с одного IP адреса (в секундах).',
'Spam check info'		=>	'Вы можете проверять IP-адрес, e-mail, имя в крупнейшей базе спамеров — <a href="http://www.stopforumspam.com">Stop Forum Spam</a>. Участнику чей IP-адрес, e-mail или имя находится в базе будет отказано в регистрации. Проверка прозрачна и не требует от регистранта никаких лишних дейстий.',
'Spam check legend'		=>	'Включить блокировку',
'Spam ip info'			=>	'Проверять IP-адрес на нахождение в базе спамеров.',
'Spam email info'		=>	'Проверять адрес e-mail на нахождение в базе спамеров.',
'Spam name info'		=>	'Проверять имя на нахождение в базе спамеров.',
'Registration rules'		=>	'Правила форума (использование и оформление правил форума)',
'Registration rules info'	=>	'Вы можете обязать пользователей принимать правила форума при регистрации (напишите их в текстовом поле ниже). Правила всегда будут доступны для просмотра по ссылке из главного меню на каждой странице форума.',
'Registration rules legend'	=>	'Правила форума',
'Require rules'			=>	'Соглашение с правилами',
'Require rules label'		=>	'Обязать участников принимать правила форума перед процедурой регистрации.',
'Compose rules label'		=>	'Правила',
'Compose rules help'		=>	'Вы можете использовать HTML в этом блоке. Оставьте пустым, чтобы не использовать правила.',
'Rules default'			=>	'Введите сюда ваши правила.',
'Username'			=>	'Имя',
'User added'   			=>	'Участник добавлен успешно.',
'Username help'  		=>	'От 2 до 25 символов.',
'E-mail help'			=>	'Введите текущий и действующий адрес электронной почты.',
'Edit user'			=>	'Редактировать участника',
'Edit help'			=>	'Редактировать информацию об участнике после добавления.',
'Add user'			=>	'Добавить участника',
'There are some errors'		=>	'<strong>Внимание!</strong> Следующие ошибки необходимо исправить, прежде чем вы сможете добавить участника:',

// Email section
'E-mail head'			=>	'E-mail-адрес форума и список рассылки',
'E-mail addresses legend'	=>	'E-mail адреса',
'Admin e-mail'			=>	'E-mail администратора',
'Webmaster e-mail label'	=>	'E-mail веб-мастера',
'Webmaster e-mail help'		=>	'Этот адрес будет указан в поле «Отправитель» во всех сообщениях, отсылаемых форумом.',
'Mailing list label'		=>	'Создать список рассылки',
'Mailing list help'		=>	'E-mail адреса, на которые будут отправляться письма с уведомлениями о новых регистрация, жалобах, следует разделять запятыми.',
'E-mail server'			=>	'Конфигурация почтового сервера для отправки сообщений форума',
'E-mail server legend'		=>	'Сервер E-mail',
'E-mail server info'		=>	'В большинстве случаев Flazy без проблем отправляет e-mail сообщения, используя внутренний почтовый сервис, в этом случае вы можете пропустить эти настройки. Но можно сконфигурировать Flazy и для использования внешнего почтового сервера. Введите адрес внешнего почтового сервера и, если требуется, назначьте номер порта, если ваш SMTP сервер не может работать через стандартный 25 порт (например: mail.example.com:3580)',
'SMTP address label'		=>	'Адрес SMTP-сервера',
'SMTP address help'		=>	'Для внешних серверов. Оставьте пустым, чтобы использовать внутреннюю почтовую службу.',
'SMTP username label'		=>	'Имя пользователя',
'SMTP help'			=>	'Не требуется большинству SMTP-серверов.',
'SMTP password label'		=>	'Пароль',
'SMTP SSL'			=>	'Шифровать соединение',
'SMTP SSL label'		=>	'Шифровать ваше SMTP соединение, используя SSL. Выбирайте, только если уверены, что ваша версия PHP поддерживает SSL и ваш SMTP сервер требует этого.',
'Error invalid admin e-mail'	=>	'E-mail адрес администратора, который вы ввели содержит ошибку.',
'Error invalid web e-mail'	=>	'E-mail адрес вебмастера, который вы ввели содержит ошибку.',
'Error no subject'		=>	'Вы не ввели тему письма.',
'Error no massage'		=>	'Вы не ввели тело письма.',
'Error no group'		=>	'Вы не выбрали группу.',
'Error no partition'		=>	'Вы не указали разбивку на части.',
'Mass e-mail'			=>	'Массовая рассылка e-mail',
'Mass subject label'		=>	'Тема',
'Mass massage label'		=>	'Сообщение',
'Mass recipient label'		=>	'Получатели',
'Mass partition label'		=>	'Разбить',
'Mass partition help'		=>	'Вы можете разбить отправку писем на несколько частей. Введите число, участников в одном отправлении. В дальшейшем при завершении отправки одной части нужно повторно нажать «Отправить» для отправки следующий части. Введите 0 если не хотите разбивать рассылку.',
'All group'			=>	'Все группы',
'Preview'			=>	'Преварительный просмотр',
'Preview mail'			=>	'Массовая рассылка — подтверждение',
'Successfully sent'		=>	'Успешно отправлено',
'Сlick only once'		=>	'Пожалуйста нажмите кнопку только однажды. Ожидайте сообщения о результате.',

// Maintenance section
'Maintenance head'		=>	'Настройка сообщения режима техобслуживания и его активация',
'Maintenance mode info'		=>	'<strong>ВАЖНО!</strong> Форум будет доступен только администраторам. Этот режим следует использовать, если нужно закрыть форум для проведения каких-либо настроек.',
'Maintenance mode warn'		=>	'<strong>ВНИМАНИЕ!</strong> НЕ ВЫХОДИТЕ, пока форум находится в режиме техобслуживания. Вы не сможете войти снова!',
'Maintenance legend'		=>	'Техобслуживания',
'Maintenance mode'		=>	'Режим техобслуживания',
'Maintenance mode label'	=>	'Перевести форум в режим техобслуживания',
'Maintenance message label'	=>	'Сообщение',
'Maintenance message help'	=>	'Сообщение, которое будет показано, когда форум переведен в режим техобслуживания. Если не хотите писать свое сообщение, будет показано сообщение по умолчанию. Для написания сообщения можно использовать HTML-код.',
'Maintenance message default'	=>	'На форуме ведутся профилактические работы. Соблюдайте спокойствие. В ближайшее время форум возобновит свою работу. Спасибо за понимание!<br /><br />Администрация',





);