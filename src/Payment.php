<?php

namespace SZonov\Sberbank\PdfAccountStatement;

/**
 * Class Payment
 * Платеж (основные данные платежа)
 *
 * @package SZonov\Sberbank\PdfAccountStatement
 *
 * @property string $date       Дата проводки (формат 'YYYY-MM-DD')
 * @property string $account    Номер лицевого счета контрагента
 * @property string $inn        ИНН контрагента
 * @property string $name       Название контрагента
 * @property string $no         № док.
 * @property string $vo         Вид операции
 * @property string $bik        БИК банка контрагента
 * @property string $bank       Название банка контрагента
 * @property float  $sum        Сумма платежа
 * @property int    $direction  Направление платежа (входящий / исходящий)
 * @property string $purpose    Назначение платежа
 *
 */
class Payment
{
    /**
     * Направление платежа: входящий / исходящий
     */
    const IN = 1; // входящий (кредит)
    const OUT = 0; // исходящий (дебет)

    private $_data = array(
        'date' => null,
        'account' => null,
        'inn' => null,
        'name' => null,
        'no' => null,
        'vo' => null,
        'bik' => null,
        'bank' => null,
        'sum' => 0,
        'direction' => self::OUT,
        'purpose' => null,
    );

    /**
     * Payment constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $val)
            $this->__set($key, $val);
    }

    public function __get($name)
    {
        return (array_key_exists($name, $this->_data)) ? $this->_data[$name] : null;
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_data))
            $this->_data[$name] = $value;
    }

    public function __toString()
    {
        $options = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        return $this->_data;
    }
}