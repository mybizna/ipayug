<?php

namespace Modules\Ipayug\Classes;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Ipay\Entities\IpayugPayment;
use Modules\Payment\Classes\PaymentProcessor;
use Modules\Payment\Entities\Payment;

class IpayugPaymentProcessor
{
    public function paymentIpayChecker(Request $request)
    {
        $paymentProcessor = new PaymentProcessor();

        $paymentIpayChecker = Cache::get('paymentIpayChecker');

        if ($paymentIpayChecker !== null) {
            return;
        }

        Cache::put('paymentIpayChecker', rand(1, 7), 3600);

        $globalPreferences = global_preferences_registry()->manager();
        $secretKey = $globalPreferences['paymentipay__secret_key'];
        $vid = $globalPreferences['paymentipay__vid'];

        $dateTo = Carbon::now()->subMinutes(15);
        $dateFrom = Carbon::now()->subMinutes(480);

        $payments = Payment::where([
            ['type', '=', 'ipayug'],
            ['successful', '=', false],
            ['is_confirmed', '=', false],
            ['created_at', '>=', $dateFrom],
            ['created_at', '<=', $dateTo],
        ])->take(100)->get();

        $gateway = $paymentProcessor->getGatewayByName('ipay');

        foreach ($payments as $payment) {
            $num = rand(1, 15);
            sleep($num / 3);

            $oid = $payment->id;

            $newParams = [
                ['oid', $oid],
                ['vid', $vid],
            ];

            $processedHash = $this->getHashForChecker($newParams);

            $newParams[] = ['hash', $processedHash];

            $queryParams = http_build_query($newParams);
            $url = "https://apis.ipayafrica.com/payments/v2/transaction/search?$queryParams";

            try {
                $response = file_get_contents($url);
                $ipayDict = json_decode($response, true);
            } catch (\Exception $ex) {
                $ipayDict = ['status' => 0];
                Log::error($ex);
            }

            if ($ipayDict['status'] == 1) {
                $payment = Payment::find($payment->id);
                $ipayugPayment = IpayugPayment::where([
                    ['item_id', '=', $payment->id],
                    ['status', '=', 'aei7p7yrx4ae34'],
                ])->first();

                if ($ipayugPayment || $payment->is_confirmed) {
                    continue;
                }

                $deductions = json_decode($payment->deductions, true) ?: [];
                $requiredAmount = isset($deductions['amount']) ? Decimal::fromString($deductions['amount']) : $payment->amount;

                $paidAmount = Decimal::fromString($ipayDict['data']['transaction_amount']);

                $paidAmount = $paymentProcessor->getGatewayConverterAmount($paidAmount, $gateway, false);
                $requiredAmount = $paymentProcessor->getGatewayConverterAmount($requiredAmount, $gateway, false);

                $paymentProcessor->savePaidAmount($payment, $requiredAmount, $paidAmount);

                if ($paidAmount >= $requiredAmount) {
                    $payment->code = $ipayDict['data']['transaction_code'];
                    $payment->save();

                    $paymentProcessor->successfulTransaction($payment, $ipayDict['data']['transaction_code']);
                } else {
                    $paymentProcessor->failTransaction($payment);
                }

                $payment->is_confirmed = true;
                $payment->save();

                IpayugPayment::create([
                    'item_id' => $payment->id,
                    'status' => 'aei7p7yrx4ae34',
                    'txncd' => $ipayDict['data']['transaction_code'],
                    'ivm' => $payment->id,
                    'mc' => $ipayDict['data']['transaction_amount'],
                    'p1' => $payment->id,
                    'p2' => '',
                    'p3' => '',
                    'p4' => '',
                    'payment' => $payment,
                ]);
            }
        }

        Cache::forget('paymentIpayChecker');
    }

    public function paymentIpayugReturn(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $phone = $request->input('phone');
        $returnUrl = $request->input('return_url');

        $phone = trim($phone);

        $payment = Payment::find($paymentId);
        $globalPreferences = global_preferences_registry()->manager();
        $secretKey = $globalPreferences['paymentipayug__secret_key'];
        $vid = $globalPreferences['paymentipayug__vid'];

        $live = '0';
        $oid = (string) $payment->id;
        $inv = (string) $payment->id;
        $amount = (string) $payment->amount_required;
        $tel = isset($payment->user->profile->phone) && $payment->user->profile->phone ? (string) $payment->user->profile->phone : '0722232323';
        $eml = (string) $payment->user->email;
        $curr = 'UGX';
        $p1 = '';
        $p2 = '';
        $p3 = '';
        $p4 = '';
        $cbk = $returnUrl;
        $cst = '0';
        $crl = '2';

        $datastring = $live . $oid . $inv . $amount . $tel .
            $eml . $vid . $curr . $p1 . $p2 . $p3 . $p4 . $cst . $cbk;
        $processedHash = $this->getHash($datastring);

        $queryParams = http_build_query([
            ['live', $live],
            ['vid', $vid],
            ['oid', $oid],
            ['inv', $inv],
            ['amount', $amount],
            ['tel', $tel],
            ['eml', $eml],
            ['curr', $curr],
            ['p1', $p1],
            ['p2', $p2],
            ['p3', $p3],
            ['p4', $p4],
            ['cbk', $cbk],
            ['cst', $cst],
            ['crl', $crl],
            ['hash', $processedHash],
        ]);

        $url = "https://apis.ipayafrica.com/payments/v2/transact?$queryParams";

        $response = file_get_contents($url);
        $ipayugDict = json_decode($response, true);

        if ($ipayugDict['status']) {
            $sid = $ipayugDict['data']['sid'];

            $datastring = $phone . $vid . $sid;
            $processedHash = $this->getHash($datastring);

            $queryParams = http_build_query([
                ['phone', $phone],
                ['vid', $vid],
                ['sid', $sid],
                ['hash', $processedHash],
            ]);

            $url = "https://apis.ipayafrica.com/payments/v2/transact/push/mpesa?$queryParams";

            $response = file_get_contents($url);
            $ipayugDict = json_decode($response, true);

            if ($ipayugDict['status']) {
                $payment->completed = true;
                $payment->save();
            }
        }

        return $payment;
    }

    public function paymentIpayugNotify(Request $request)
    {
        $paymentId = $request->query('id');

        if (!$paymentId) {
            $paymentId = $request->query('id');
        }

        $payment = Payment::find($paymentId);
        $ipayugData = $this->getIpayParams($payment, $request);

        if (!$payment->successful) {
            $this->processIpayugpayment($payment, $ipayugData);
        }

        return $payment;
    }

    private function getHash($datastring)
    {
        $globalPreferences = global_preferences_registry()->manager();
        $vid = $globalPreferences['paymentipayug__vid'];
        $secretKey = $globalPreferences['paymentipayug__secret_key'];

        $rawDatastring = $this->toRaw($datastring);
        $signature = hash_hmac('sha256', $rawDatastring, $secretKey);

        return $signature;
    }

    private function toRaw($string)
    {
        return $string;
    }

    private function processIpayugpayment($payment, $ipayugData)
    {
        $paymentProcessor = new PaymentProcessor();

        $gateway = $paymentProcessor->getGatewayByName('ipayug');
        $deductions = json_decode($payment->deductions, true) ?: [];
        $requiredAmount = isset($deductions['amount']) ? Decimal::fromString($deductions['amount']) : $payment->amount;
        $paidAmount = Decimal::fromString($ipayugData['mc'] ?? '0');

        $paidAmount = $paymentProcessor->getGatewayConverterAmount($paidAmount, $gateway, false);
        $requiredAmount = $paymentProcessor->getGatewayConverterAmount($requiredAmount, $gateway, false);

        $globalPreferences = global_preferences_registry()->manager();
        $vendorRef = $globalPreferences['paymentipayug__vid'];

        $queryParams = http_build_query([
            ['vendor', $vendorRef],
            ['id', $ipayugData['item_id']],
            ['ivm', $ipayugData['ivm']],
            ['qwh', $ipayugData['qwh']],
            ['afd', $ipayugData['afd']],
            ['poi', $ipayugData['poi']],
            ['uyt', $ipayugData['uyt']],
            ['ifd', $ipayugData['ifd']],
        ]);

        $url = "https://payments.elipa.co.ug/v3/ug/ipn/?$queryParams";

        $response = file_get_contents($url);
        $status = trim($response);

        $payment->gateway = $gateway;
        $payment->completed = true;
        $payment->save();

        if (in_array($status, ['aei7p7yrx4ae34', 'eq3i7p5yt7645e', 'dtfi4p7yty45wq'])) {
            $paymentProcessor->savePaidAmount($payment, $requiredAmount, $paidAmount);

            if ($paidAmount >= $requiredAmount) {
                $paymentProcessor->successfulTransaction($payment, $ipayugData['txncd']);
            } else {
                $paymentProcessor->failTransaction($payment);
            }
        } elseif (in_array($status, ['fe2707etr5s4wq', 'cr5i3pgy9867e1'])) {
            $paymentProcessor->failTransaction($payment);
        } elseif ($status === 'bdi6p2yy76etrs' || $status === '') {
            $paymentProcessor->pendingTransaction($payment);
        }
    }

    private function getIpayParams($payment, $request)
    {
        $txncd = $request->query('txncd');
        $ipayugPaymentAdded = IpayugPayment::where('txncd', $txncd)->first();

        if (!$ipayugPaymentAdded) {
            $ipayugPayment = new IpayugPayment([
                'payment_id' => $payment->id,
                'item_id' => $request->query('id'),
                'status' => $request->query('status'),
                'txncd' => $txncd,
                'ivm' => $request->query('ivm'),
                'qwh' => $request->query('qwh'),
                'afd' => $request->query('afd'),
                'poi' => $request->query('poi'),
                'uyt' => $request->query('uyt'),
                'ifd' => $request->query('ifd'),
                'agd' => $request->query('agd'),
                'mc' => $request->query('mc'),
                'p1' => $request->query('p1'),
                'p2' => $request->query('p2'),
                'p3' => $request->query('p3'),
                'p4' => $request->query('p4'),
            ]);

            $ipayugPayment->save();

            return $ipayugPayment->toArray();
        }

        return $ipayugPaymentAdded->toArray();
    }
}
