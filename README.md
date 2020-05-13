# ramtv parser

Приложение для парсинга контента с сайта ramtv.ru и конвертации в формат wordpress.

### Требования:

 * php 7.2
 * php-imagick (jpeg support)
 * php-mbstring
 * php-curl
 * php-dom
 * php-xml
 * ffmpeg
 * composer

### Установка:
```sh
apt install php-cli php-imagick php-mbstring php-curl php-dom php-xml ffmpeg composer
git clone git@github.com:PavelPronskiy/ramtv-parser.git
cd ramtv-parser/
composer update
composer show
./bin/parser
```
### Параметры:

    [-v] -- вывод отладочной информации
    [-t] -- запуск в тестовом режиме
    [-d] -- запуск в режиме сохранения ранжированием по датам

### Примеры:

Запуск в отладочном режиме с ранжированием по дате:
```sh
./bin/parser -v -t -d 01.2002-12.2020
```

Запуск парсера с сохранением в wordpress базу:
```sh
./bin/parser -d 01.2002-12.2020
```

### Конфигурация:
    - config.json -- вся конфигурация приложения


