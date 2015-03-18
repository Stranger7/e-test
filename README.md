Тестовое задание
================
http://path_to_project/site/

Requirements
------------
+ PHP 5.5
+ MySQL
+ Если используется web-сервер Apache, то необходимо подключение `mod_rewrite`


Installation
------------
Создать БД, пользователя и назначить ему права. При необходимости изменить имя пользователя,
БД и пароль в конфиг-файлах web_config.ini и cli_config.ini в секции [db:test]
```
CREATE DATABASE e_test_93451 CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'e_test_93451'@'localhost' IDENTIFIED BY '1234';
GRANT ALL ON e_test_93451.* TO 'e_test_93451'@'localhost';
```

Накатить миграции:
```
cd path/to/project/console
php crystal.php /core/DbUpgrade init
php crystal.php /core/DbUpgrade up
```

Убедиться, что каталог для лог-файлов доступен для записи. Путь для него можно задать 
в конфиг-файлах web_config.ini и cli_config.ini [logger] параметром 'filename'. По умолчанию логи
пишутся в католог 'logs' приложения.
Пример секции:
```
[logger]
driver = "\core\loggers\BufferedLogger"
levels = ALL
filename = с:\app.log
```
