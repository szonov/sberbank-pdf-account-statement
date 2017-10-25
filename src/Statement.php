<?php

namespace SZonov\Sberbank\PdfAccountStatement;

use SZonov\Text\Parser\ParserIterator as Iterator;
use SZonov\Text\Source\Command;

/**
 * Class Statement
 * Выписка: коллекция банковских платежей, получаемая из PDF файла сгенерированного в Сбербанк Бизнес Онлайн
 *
 * *** ВАЖНО: Требуется установленная консольная команда 'pdftohtml',
 *
 * При установке в нестандартные пути pdftohtml - путь к ней можно передать вторым аргументом при создании класса
 *
 * @package SZonov\Bank\PdfAccountStatement
 *
 * @property string         $file           Имя файла из которого получена текущая коллекция
 * @property string         $account        Числовая строка - номер лицевого счета
 * @property Payment[]      $payments       Массив платежей в запрошенном файле
 * @property Statistic      $statistic      Объект статистики по данным из запрошенного файла
 * @property Iterator       $iterator       Итератор по данным, не рекомендован к общему использованию,
 * @property Parser         $parser         Парсер выписок - для внутреннего использования
 *
 */
class Statement
{
    /**
     * @var string
     */
    private $_file;

    /**
     * @var string
     */
    private $_pdfToHtml;

    /**
     * @var Parser
     */
    private $_parser;

    /**
     * @var Iterator
     */
    private $_iterator;

    /**
     * @var Payment[]
     */
    private $_payments;

    /**
     * @var Statistic
     */
    private $_statistic;

    /**
     * Statement constructor.
     *
     * @param string $file - путь к файлу
     * @param string $pdfToHtml - путь к бинарнику pdftohtml, если находится в PATH - то определять не обязательно
     */
    public function __construct($file, $pdfToHtml = null)
    {
        $this->_file = $file;
        $this->_pdfToHtml = ($pdfToHtml !== null) ? $pdfToHtml : 'pdftohtml';
    }

    public function __get($name)
    {
        switch ($name)
        {
            case 'file':
                return $this->_file;

            case 'account':
                return $this->parser->getAccount();

            case 'iterator':
                return ($this->_iterator !== null) ? $this->_iterator : $this->_iterator = new Iterator($this->parser);

            case 'parser':
                return ($this->_parser !== null) ? $this->_parser : $this->_parser = $this->getParser();

            case 'payments':
                return ($this->_payments !== null) ? $this->_payments : $this->_payments = $this->getPayments();

            case 'statistic':
                return ($this->_statistic !== null) ? $this->_statistic : $this->_statistic = new Statistic($this);
        }
    }

    /**
     * @return Parser
     */
    private function getParser()
    {
        $cmd = escapeshellcmd($this->_pdfToHtml) . " -i -xml -enc UTF-8 -stdout " . escapeshellarg($this->_file);
        return new Parser(new Command($cmd));
    }

    /**
     * @return Payment[]
     */
    private function getPayments()
    {
        $payments = array();
        if ($this->iterator->key() !== 0)
            $this->iterator->rewind();
        foreach ($this->iterator as $payment)
            $payments[] = $payment;
        return $payments;
    }
}