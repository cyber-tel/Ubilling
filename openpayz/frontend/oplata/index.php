<?php

// подключаем API OpenPayz

include ("../../libs/api.openpayz.php");

$rawRequest = file_get_contents("php://input");

if (!empty($rawRequest)) {
    $requestData = json_decode($rawRequest);
    // print_r($requestData);
    if (!empty($requestData)) {
        if (isset($requestData->amount)) {

            if (isset($requestData->merchant_data)) {
                $merchantDataRaw = $requestData->merchant_data;
                $merchantData = json_decode($merchantDataRaw);
                if (isset($merchantData[0])) {
                    $merchantData = $merchantData[0];
                    if ($merchantData->name == 'paymentid') {
                        $customerId = $merchantData->value;
                        $allcustomers = op_CustomersGetAll();
                        if (isset($allcustomers[$customerId])) {
                            $paysys = 'OPLATA';
                            $hash = 'OPLT_' . $requestData->payment_id;
                            $summ = $requestData->amount / 100; // деньги то в копейках
                            //регистрируем новую транзакцию
                            op_TransactionAdd($hash, $summ, $customerId, $paysys, 'NOPE');
                            //вызываем обработчики необработанных транзакций
                            op_ProcessHandlers();
                        }
                    }
                }
            }
        }
    }
}
?>