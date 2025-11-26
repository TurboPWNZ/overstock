<?php
namespace Slando\core;

use Slando\core\libs\Base64Url;
use Slando\core\payment\privat\Transaction;

class Bank
{
    const QR_LINK_DOMAIN = 'https://bank.gov.ua/qr/';

    const QR_DATA_TEMPLATE = 'BCD
002
1
UCT

{:sellerName}
{:sellerIBAN}
UAH{:depositAmount}
{:sellerEDRPOU}


{:description}

';

    public static function scheduler()
    {
        Transaction::process();
    }

    public static function getPaymentLink($transactionID, $amount)
    {
        return (new self())->getQrCode($transactionID, $amount);
    }

    protected function getQrCode($transactionID, $amount)
    {
        $qrCodeData = self::QR_DATA_TEMPLATE;
        $placeholderData = $this->prepareQrDataPlaceholder($transactionID, $amount);
        foreach ($placeholderData as $placeholder => $data) {
            $qrCodeData = str_replace($placeholder, $data, $qrCodeData);
        }

        $base64encoded = Base64Url::encode($qrCodeData);

        return self::QR_LINK_DOMAIN . $base64encoded;
    }

    protected function prepareQrDataPlaceholder($transactionID, $amount)
    {
        $config = Configurator::load();

        return [
            '{:sellerName}' => $config['params']['secrets']['bank']['seller'],
            '{:sellerIBAN}' => $config['params']['secrets']['bank']['account'],
            '{:depositAmount}' => $amount,
            '{:sellerEDRPOU}' => $config['params']['secrets']['bank']['edrpou'],
            '{:description}' => $transactionID . '; '
                . $config['params']['secrets']['bank']['purpose']
        ];
    }
}