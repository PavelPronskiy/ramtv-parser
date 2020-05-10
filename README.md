# ramtv parser

Приложение для парсинга контента с сайта ramtv.ru и конвертации в формат wordpress.

### Требования:

 * apt install php-imagick ffmpeg composer
 * cd ramtv-parser/
 * composer require

### Установка:
```sh
git clone git@github.com:PavelPronskiy/ramtv-parser.git
cd ramtv-parser/
./bin/parser
```
### Параметры:

    [-v] -- вывод отладочной информации
    [-t] -- запуск в тестовом режиме
    [-d] -- запуск в режиме сохранения ранжированием по датам

### Примеры:

Запуск в отладочном режиме с ранжированием по дате
```sh
./bin/parser -v -t -d 01.2002-12.2020
```

Запуск парсера с сохранением в wordpress базу
```sh
./bin/parser -d 01.2002-12.2020
```

### Конфигурация:
    - config.json -- вся конфигурация приложения


