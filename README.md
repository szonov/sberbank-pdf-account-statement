# szonov/sberbank-pdf-account-statement
Парсинг PDF файлов с выписками из Сбербанк Бизнес Онлайн

Требования
-------------
Установленная консольная утилита `pdftohtml`

Пример использования
-------------

```php

include "vendor/autoload.php";

use SZonov\Sberbank\PdfAccountStatement\Statement;

$statement = new Statement(__DIR__ . '/выписка.pdf');

echo "АККАУНТ: {$statement->account}\n";

echo "СТАТИСТИКА:\n";
print_r($statement->statistic->toArray());

echo "ПЛАТЕЖИ:\n";
print_r($statement->payments);

```