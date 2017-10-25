<?php

namespace SZonov\Sberbank\PdfAccountStatement;

/**
 * Class Payment
 * Платеж (основные данные платежа)
 *
 * @package SZonov\Sberbank\PdfAccountStatement
 */
class Payment
{
    /**
     * Направление платежа: входящий / исходящий
     */
    const IN = 1; // входящий (кредит)
    const OUT = 0; // исходящий (дебет)

    /**
     * Дата проводки (формат 'YYYY-MM-DD')
     */
    protected $date;

    /**
     * Номер лицевого счета контрагента (наш идентификатор)
     */
    protected $account;

    /**
     * ИНН контрагента
     */
    protected $inn;

    /**
     * Название контрагента
     */
    protected $name;

    /**
     * № док.
     */
    protected $no;

    /**
     * Вид операции
     */
    protected $vo;

    /**
     * БИК банка контрагента
     */
    protected $bik;

    /**
     * Название банка контрагента
     */
    protected $bank;

    /**
     * Сумма платежа
     */
    protected $sum = 0;

    /**
     * Направление платежа (входящий / исходящий)
     */
    protected $direction = self::OUT;

    /**
     * Назначение платежа
     */
    protected $purpose;

    /**
     * Payment constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $val)
        {
            if (method_exists($this, $method = 'set' . ucfirst($key)))
                $this->$method($val);
        }
    }

    /**
     * Получение даты платежа
     *
     * @return string (в формате YYYY-MM-DD)
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Установка даты платежа
     *
     * @param string $value  (формат YYYY-MM-DD)
     * @return $this
     */
    public function setDate($value)
    {
        $this->date = $value;
        return $this;
    }

    /**
     * Получение лицевого счета конрагента
     *
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Установка лицевого счета конрагента
     *
     * @param mixed $value
     * @return $this
     */
    public function setAccount($value)
    {
        $this->account = $value;
        return $this;
    }

    /**
     * Получение имени контрагента
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Установка имени контрагента
     *
     * @param string $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * Получение ИНН
     *
     * @return int
     */
    public function getInn()
    {
        return $this->inn;
    }

    /**
     * Установка ИНН
     *
     * @param int $value
     * @return $this
     */
    public function setInn($value)
    {
        $this->inn = $value;
        return $this;
    }

    /**
     * Получение № док.
     *
     * @return mixed
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Установка № док.
     * @param mixed $value
     * @return $this
     */
    public function setNo($value)
    {
        $this->no = $value;
        return $this;
    }

    /**
     * Получение вида операции
     *
     * @return mixed
     */
    public function getVo()
    {
        return $this->vo;
    }

    /**
     * Установка вида операции
     *
     * @param mixed $value
     * @return $this
     */
    public function setVo($value)
    {
        $this->vo = $value;
        return $this;
    }

    /**
     * Получение БИКа банка контрагента
     *
     * @return int
     */
    public function getBik()
    {
        return $this->bik;
    }

    /**
     * Установка БИКа банка контрагента
     *
     * @param int $value
     * @return $this
     */
    public function setBik($value)
    {
        $this->bik = $value;
        return $this;
    }

    /**
     * Получение банка контрагента
     *
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Установка банка контрагента
     *
     * @param string $value
     * @return $this
     */
    public function setBank($value)
    {
        $this->bank = $value;
        return $this;
    }

    /**
     * Получение суммы платежа
     *
     * @return float
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Установка суммы платежа
     *
     * @param float $value
     * @return $this
     */
    public function setSum($value)
    {
        $this->sum = $value;
        return $this;
    }

    /**
     * Получение назначения платежа
     *
     * @return string
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * Установка назначения платежа
     *
     * @param string $purpose
     * @return $this
     */
    public function setPurpose($purpose)
    {
        $this->purpose = $purpose;
        return $this;
    }

    /**
     * Получение направления платежа (дебет/кредит)
     *
     * @return int
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Установка направления платежа (дебет/кредит)
     *
     * @param int $direction
     * @return $this
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    public function __toString()
    {
        return (string)print_r($this->toArray(), true);
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}