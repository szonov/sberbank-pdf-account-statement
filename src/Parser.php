<?php

namespace SZonov\Sberbank\PdfAccountStatement;

use SZonov\Text\Parser\ParserInterface;
use SZonov\Text\Source\SourceInterface;

/**
 * Парсинг выписок из сбербанка - на вход подается источник, который отдает эти выписки в XML формате
 * (преобразование после pdftohtml)
 *
 * Class Parser
 * @package SZonov\Sberbank\PdfAccountStatement
 */
class Parser implements ParserInterface
{
    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @var array
     */
    protected $buffer = array();

    /**
     * Номер лицевого счета по которому сделана текущая выписка операций
     * В PDF файле идет на первой же странице:
     *
     * <bold>ВЫПИСКА ОПЕРАЦИЙ ПО ЛИЦЕВОМУ СЧЕТУ</bold> 40140140140140140140
     *
     * @var null|int
     */
    protected $account = null;

    /**
     * Parser constructor.
     *
     * @param SourceInterface $source
     */
    public function __construct(SourceInterface $source)
    {
        $this->source = $source;
    }

    /**
     * Сброс всей распарсенной информации и всё сначала
     */
    public function rewind()
    {
        $this->buffer= array();
        $this->account = null;
        $this->source->rewind();
    }

    /**
     * Получение следующей записи
     *
     * Пример массива на выходе:
     * [
     *    0 => '2016-10-10',                                        // Дата платежа
     *    1 => [
     *      0 => '40000100100100100101',                            // Отправитель: номер счета
     *      1 => '5544332211',                                      // Отправитель: ИНН
     *      2 => 'ООО "Рога и копыта"',                             // Отправитель: Наименование
     *    ],
     *    2 => [
     *      0 => '40000100100100100102',                            // Получатель: номер счета
     *      1 => '5544332212',                                      // Получатель: ИНН
     *      2 => 'ООО "Другая компания"',                           // Получатель: Наименование
     *    ],
     *    3 => 10511.15,                                            // Сумма платежа
     *    4 => "100",                                               // No документа
     *    5 => "01",                                                // ВО
     *    6 => [
     *      0 => "040404040",                                       // БИК банка
     *      1 => "БАНК БЕЗ ИМЕНИ, Г. МОСКВА",                       // Наименование банка
     *    ]
     *    7 => "Комм. услуги за сентябрь 2016, НДС не облагается",  // Назначение платежа
     *  ]
     *
     * @return bool|Payment
     */
    public function getItem()
    {
        if (empty($this->buffer))
            return ($this->loadOnePage()) ? $this->getItem() : false;

        while ($row = array_shift($this->buffer))
        {
            $item = $this->rowTransform($row);

            if ($item) {
                return $item;
            }
        }

        return $this->getItem();
    }

    /**
     * Публичное получение текущего номера счета
     *
     * @return int|null
     */
    public function getAccount()
    {
        // так как информация о номере счета всегда находится на первой странице
        // то в случае отсутствия этого номера и при пустом буфере нам нужно подгрузить страницу
        // если же буфер не пустой - мы уже получали первую страницу и отсутствие account будет говорить о проблеме
        // Поэтому рекомендуется перед итерированием по записям дернуть эту функцию и убедиться что отдан не NULL
        if (!$this->account && empty($this->buffer))
            $this->loadOnePage();

        return $this->account;
    }

    /**
     * Читаем файл пока не найдем строку вида
     *   <page number="1" position="absolute" top="0" left="0" height="892" width="1263">
     * @return bool
     */
    protected function jumpToPage()
    {
        while (false !== ($line = $this->source->getLine())) {
            if (preg_match('/^<page /', trim($line)))
                return true;
        }
        return false;
    }

    /**
     * Загрузка в буфер данных следующей страницы PDF файла
     *
     * @return bool
     */
    protected function loadOnePage()
    {
        if (!$this->jumpToPage())
            return false;

        $this->buffer = array();

        $rowIndex  = -1;
        $cellIndex = 0;
        $prevOffset= 10000;

        // если не определен свой лицевой счет, то будем собирать весь текст страницы в одну строку
        // что бы вычислить затем из неё этот номер
        // для оптимизации - собираем только первые 20 строк - он точно находится в начале страницы
        $pageText     = '';
        $pageLines    = 0;
        $pageLinesMax = 20;

        while (false !== ($line = $this->source->getLine()))
        {
            $line = trim($line);

            // нашли конец страницы? закончили с одной страницей
            if (preg_match('/^<\/page>$/', $line))
                break;

            // не текстовые данные? пропустим эту строку
            if (!preg_match('/^<text[^>]+left="(\d+)"[^>]+>(.*)<\/text>$/ms', $line, $regs))
                continue;

            list(, $offset, $value) = $regs;

            $value = $this->textValueTransform($value);

            if (!$this->account && $value !== "" && $pageLines < $pageLinesMax) {
                $pageText .= " " . $value;
                $pageLines++;
            }

            $diff = $offset - $prevOffset;

            if ($diff > 5) { // следующая ячейка
                $cellIndex++;
            } else if ($diff < -5 ){ // следующая строка
                $rowIndex++;
                $cellIndex=0;
                $this->buffer[$rowIndex] = array();
            }

            if (!isset($this->buffer[$rowIndex][$cellIndex]))
                $this->buffer[$rowIndex][$cellIndex] = $value;
            else
                $this->buffer[$rowIndex][$cellIndex] .= " " . $value;

            $prevOffset = $offset;
        }

        // Определяем номер лицевого счета
        // согласно википедии
        // https://ru.wikipedia.org/wiki/%D0%A0%D0%B0%D1%81%D1%87%D1%91%D1%82%D0%BD%D1%8B%D0%B9_%D1%81%D1%87%D1%91%D1%82
        // в номер счета должно быть 20 цифр (но предусмотрена возможность до 25 значков)
        // на первой странице такое кол-во знаков может быть только у лицевого счета - вот и ищем такой набор цифр

        if (!$this->account && preg_match('/\b(\d{20,25})\b/', $pageText, $regs))
            $this->account = $regs[1];

        return true;
    }

    /**
     * Проверка данных одной строки таблицы
     * - если в них есть все данные платежа то возвращаются преобразованные данные
     * - если нет - возвращается FALSE
     *
     * @param array $row
     * @return Payment|bool
     */
    protected function rowTransform($row)
    {

        $ret = array();
        $index = 0;

        // кол-во элементов с платежом не менее 7
        if (count($row) < 7)
            return false;

        // [0] дата (формат DD.MM.YYYY)
        if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $row[$index++], $regs))
            return false;

        $ret[] = $regs[3].'-'.$regs[2].'-'.$regs[1];

        // [1] плательщик (40140140140140140140 5405405405 ООО ...)
        if ( ! ($info = $this->companyInfo($this->textValueTransform($row[$index++]))) )
            return false;
        $ret[] = $info;

        // [2] получатель (40140140140140140140 5405405405 ООО ...)
        if ( ! ($info = $this->companyInfo($this->textValueTransform($row[$index++]))) )
            return false;
        $ret[] = $info;

        // [3] или [4] = следующая не пустая - это сумма
        $value = $row[$index++];
        if ($value === "")
            $value = $row[$index++];
        $ret[] = $this->priceValueTransform($value);

        // следующие элементы (до тех пор пока не встретится БИК) являются "No документа" "ВО"
        $no_vo = $row[$index++];
        $bik_found = false;

        while ($index < count($row))
        {
            $value = $this->textValueTransform($row[$index++]);

            if (preg_match('/^БИК (\d+),?(.*)$/', $value, $regs))
            {
                $bik_found = true;

                $a = explode(" ", trim(preg_replace('/\s+/', ' ', $no_vo)));
                $ret[] = $a[0];
                $ret[] = isset($a[1]) ? $a[1] : '';

                // [LAST-1] Банк (БИК и наименование)
                $ret[] = array($regs[1], trim($regs[2]));

                // [LAST] Назначение платежа
                $ret[] = $this->textValueTransform($row[$index]);
                break;
            }
            $no_vo .= ' ' . $value;
        }
        return ($bik_found) ? $this->rowToPayment($ret) : false;
    }

    /**
     * @param array $row
     * @return Payment
     */
    protected function rowToPayment($row)
    {
        $direction = ($this->getAccount() == $row[1][0]) ? Payment::OUT : Payment::IN;
        $contractor = ($direction === Payment::OUT) ? $row[2] : $row[1];

        return new Payment(array(
            'direction' => $direction,
            'date' => $row[0],
            'sum' => $row[3],
            'no' => $row[4],
            'vo' => $row[5],
            'bik' => $row[6][0],
            'bank' => $row[6][1],
            'purpose' => $row[7],
            'account' => $contractor[0],
            'inn' => $contractor[1],
            'name' => $contractor[2]
        ));
    }

    protected function companyInfo($value)
    {
        if (preg_match('/^(\d{10,}) (\d{10,12}) (.+)$/', $value, $regs))
            return array($regs[1], $regs[2], $regs[3]);

        if (preg_match('/^(\d{10,}) (.+)$/', $value, $regs))
            return array($regs[1], '', $regs[2]);

        return false;
    }

    protected function textValueTransform($value)
    {
        // убираем кодированные HTML символы
        $value = strip_tags(htmlspecialchars_decode($value));

        // убираем уникодные пробелы
        $value = str_replace("\xe0\xb8\x80", "\x20", $value);

        // заменяем много пробелов на один и тримаем значение
        $value = trim(preg_replace('/\s+/iu', ' ', $value));

        return $value;
    }

    protected function priceValueTransform($value)
    {
        $value = preg_replace('/[^0-9,\.]+/', '', $value);
        $value = str_replace(',', '.', $value);
        $value = (float)$value;

        return $value;
    }
}