<?php

namespace Mollie\DTO;

class OrderStateData
{
    /** @var string */
    private $name;
    /** @var bool */
    private $sendEmail;
    /** @var string */
    private $color;
    /** @var bool */
    private $logable;
    /** @var bool */
    private $delivery;
    /** @var bool */
    private $invoice;
    /** @var bool */
    private $shipped;
    /** @var bool */
    private $paid;
    /** @var string */
    private $template;
    /** @var bool */
    private $pdfInvoice;

    public function __construct(
        $name,
        $color,
        $sendEmail = false,
        $logable = false,
        $delivery = false,
        $invoice = false,
        $shipped = false,
        $paid = false,
        $template = '',
        $pdfInvoice = false
    ) {
        $this->name = $name;
        $this->sendEmail = $sendEmail;
        $this->color = $color;
        $this->logable = $logable;
        $this->delivery = $delivery;
        $this->invoice = $invoice;
        $this->shipped = $shipped;
        $this->paid = $paid;
        $this->template = $template;
        $this->pdfInvoice = $pdfInvoice;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isSendEmail()
    {
        return $this->sendEmail;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @return bool
     */
    public function isLogable()
    {
        return $this->logable;
    }

    /**
     * @return bool
     */
    public function isDelivery()
    {
        return $this->delivery;
    }

    /**
     * @return bool
     */
    public function isInvoice()
    {
        return $this->invoice;
    }

    /**
     * @return bool
     */
    public function isShipped()
    {
        return $this->shipped;
    }

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->paid;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return bool
     */
    public function isPdfInvoice()
    {
        return $this->pdfInvoice;
    }
}
