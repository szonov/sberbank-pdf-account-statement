<?php

namespace SZonov\Sberbank\PdfAccountStatement;

/**
 * Class Statistic
 * Статистические данные по выписке
 *
 * @package SZonov\Sberbank\PdfAccountStatement
 *
 * @property int $op_total   Общее кол-во операций
 * @property int $op_in      Количество входящий операций
 * @property int $op_out     Количество исходящих операций
 * @property float $in       Сумма входящих платежей
 * @property float $out      Сумма исходящих платежей
 * @property float $balance  Баланс (разница между суммой входящих и исходящих платежей)
 *
 */
class Statistic
{
    /**
     * @var Statement
     */
    private $statement;

    /**
     * Просчитанные данные (кэш)
     *
     * @var array
     */
    private $_cache;

    /**
     * Statistic constructor.
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
    }

    private function calculate()
    {
        $this->_cache = array(
            'op_total' => 0,
            'op_in' => 0,
            'op_out' => 0,
            'in' => 0,
            'out' => 0,
            'balance' => 0
        );
        foreach ($this->statement->payments as $item) {
            $this->_cache['op_total']++;

            if ($item->getDirection() === Payment::IN) {
                $this->_cache['op_in']++;
                $this->_cache['in'] += $item->getSum();
                $this->_cache['balance'] += $item->getSum();
            } else {
                $this->_cache['op_out']++;
                $this->_cache['out'] += $item->getSum();
                $this->_cache['balance'] -= $item->getSum();
            }
        }
        return $this;
    }

    public function __get($name)
    {
        $data = $this->toArray();
        return (array_key_exists($name, $data)) ? $data[$name] : null;
    }

    public function toArray() {
        if (!$this->_cache)
            $this->calculate();
        return $this->_cache;
    }
}