Тестовое задание. Написать функцию формирования sql-запросов (MySQL) из шаблона и значений параметров.

Места вставки значений в шаблон помечаются вопросительным знаком, после которого может следовать спецификатор преобразования.
Спецификаторы:
?d - конвертация в целое число
?f - конвертация в число с плавающей точкой
?a - массив значений
?# - идентификатор или массив идентификаторов

Если спецификатор не указан, то используется тип переданного значения, но допускаются только типы string, int, float, bool (приводится к 0 или 1) и null.
Параметры ?, ?d, ?f могут принимать значения null (в этом случае в шаблон вставляется NULL).
Строки и идентификаторы автоматически экранируются.

Массив (параметр ?a) преобразуется либо в список значений через запятую (список), либо в пары идентификатор и значение через запятую (ассоциативный массив).
Каждое значение из массива форматируется в зависимости от его типа (идентично универсальному параметру без спецификатора).

Также необходимо реализовать условные блоки, помечаемые фигурными скобками.
Если внутри условного блока есть хотя бы один параметр со специальным значением, то блок не попадает в сформированный запрос.
Специальное значение возвращается методом skip.
Условные блоки не могут быть вложенными.

При ошибках в шаблонах или значениях выбрасывать исключения.

В файле Database.php находится заготовка класса с заглушками в виде исключений.
В файле DatabaseTest.php находятся примеры.

Для запуска в докере

1. (!!!необязательно, если файла нет - будут использоваться дефолтные параметры)
    Скопировать файл .env.example в .env (cp .env.example .env)
    и прописать в нем параметры подключения к бд

2. запустить скрипт sh ./build.sh для сборки контейнера
3. Запустить скрипт sh ./start.sh для запуска контейнера, результаты будут выведены в STDOUT

Для отката к изначальному состоянии у уборки контейнера и образа запустить скрипт sh ./clean.sh

P.S. в test.php добавил функцию array_is_list на случай если запускаете не из докера и используется php < 8.1