<?php


require 'phpspreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;


header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');
header("HTTP/1.1 200 OK");

// Takes raw data from the request
$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json);


$headers = apache_request_headers();

$ch = curl_init();
if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    extract($_GET);
    extract($_POST);
    http_response_code(200);
} else {
    http_response_code(500);
    echo 'Server error!';
    die;
}

if (!isset($p)) {
    http_response_code(400);
    echo 'Bad Request!';
    die;
}
//local env

// curl_setopt($ch, CURLOPT_URL, "http://localhost:4000/api/v1/export/{$data->slug}/{$data->codename}?p={$p}");

//prod env
curl_setopt($ch, CURLOPT_URL, "https://jbwebapps.ga/gmims-new/api/v1/export/{$data->slug}/{$data->codename}?p={$p}");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/x-www-form-urlencoded',
    'Content-type: application/x-www-form-urlencoded',

));
$params = http_build_query($data->clusters);
$cleanedParams1 = preg_replace('/0%5B/', '', $params);
$cleanedParams2 = preg_replace('/%5D/', '', $cleanedParams1);

curl_setopt($ch, CURLOPT_POSTFIELDS, $cleanedParams2);

$response = curl_exec($ch);
curl_close($ch);

$dataResponse = json_decode($response, true);

if ($dataResponse['status'] === 400) {
    http_response_code(400);
    echo 'Bad Request';
    return;
}

if ($p === '4') {
    $spreadsheet = IOFactory::load("templates/excel/4-ccl-p2.xlsx");
    $worksheet = $spreadsheet->getActiveSheet();
    $worksheet->setTitle('4-ccl');
} else if ($p === '2') {
    $length1 = count($dataResponse['msg'][0]['gpClients']);
    $length2 = count($dataResponse['msg'][1]['gpClients']);

    if ($length1 <= 10 && $length2 <= 10) {
        $spreadsheet = IOFactory::load("templates/excel/2-ccl-p10.xlsx");
    } else if ($length1 <= 15 && $length2 <= 15) {
        $spreadsheet = IOFactory::load("templates/excel/2-ccl-p15.xlsx");
    } else {
        $spreadsheet = IOFactory::load("templates/excel/2-ccl-p20.xlsx");
    }


    $worksheet = $spreadsheet->getActiveSheet();
    $worksheet->setTitle('2-ccl');
} else {
    http_response_code(400);
    echo 'Bad Request';
    return;
}

# -------- cluster 1 -------- #

function dateDifference($date_1, $date_2, $differenceFormat)
{
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);
    $interval = date_diff($datetime1, $datetime2);
    return $interval->format($differenceFormat);
}

$filename = [];

foreach ($dataResponse['msg'] as $key => $cluster) {
    array_push($filename, strtoupper("{$cluster['staffCodeNameId']} - {$cluster['clusterCode']}"));
}

if ($p === '4') {
    $infoReSetRow1 = 4;
    $infoReSetRow2 = 5;

    $rowClientStart1 = 7;
    $rowClientStart1_spacing = 0;

    foreach ($dataResponse['msg'] as $key => $cluster) {
        array_push($filename, strtoupper("{$cluster['staffCodeNameId']} - {$cluster['clusterCode']}"));
        $worksheet->SetCellValue('C' . $infoReSetRow1, strtoupper($cluster['staffName']));
        $worksheet->SetCellValue('C' . $infoReSetRow2, strtoupper("{$cluster['staffCodeNameId']} - {$cluster['clusterCode']}"));
        $worksheet->SetCellValue('G' . $infoReSetRow1, date_format(date_create($cluster['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G' . $infoReSetRow2, date_format(date_create($cluster['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K' . $infoReSetRow1, date('F j, Y'));
        $worksheet->SetCellValue('K' . $infoReSetRow2, $cluster['weeksToPay'] . ' Weeks');
        $infoReSetRow1 += 10;
        $infoReSetRow2 += 10;


        foreach ($cluster['gpClients'] as $key1 => $clients) {

            $worksheet->SetCellValue("A" . ($rowClientStart1 + $rowClientStart1_spacing), $key1 + 1);
            $worksheet->SetCellValue("B" . ($rowClientStart1 + $rowClientStart1_spacing), strtoupper("{$clients['clientInfo']['firstName']} {$clients['clientInfo']['middleInitial']} {$clients['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . ($rowClientStart1 + $rowClientStart1_spacing), "{$clients['lr']}");
            $worksheet->SetCellValue("D" . ($rowClientStart1 + $rowClientStart1_spacing), "{$clients['skCum']}");
            $worksheet->SetCellValue("E" . ($rowClientStart1 + $rowClientStart1_spacing), "{$clients['pastDue']}");
            $worksheet->SetCellValue("G" . ($rowClientStart1 + $rowClientStart1_spacing), "{$clients['wi']}");
            $worksheet->SetCellValue("K" . ($rowClientStart1 + $rowClientStart1_spacing), round(dateDifference($cluster['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClientStart1++;
        }
        $length = count($cluster['gpClients']);
        if ($length < 2) {
            $rowClientStart1_spacing += 9;
        } else {
            $rowClientStart1_spacing += 8;
        }
    }
} else if ($p === '2') {


    $clusterSet1 = $dataResponse['msg'][0];
    $clusterSet2 = $dataResponse['msg'][1];



    if (count($clusterSet1['gpClients']) <= 10 && count($clusterSet2['gpClients']) <= 10) {
        $rowClient1Start1 = 7;
        $rowClient2Start2 = 25;
        //cluster 1
        $worksheet->SetCellValue('C4', strtoupper($clusterSet1['staffName']));
        $worksheet->SetCellValue('C5', strtoupper("{$clusterSet1['staffCodeNameId']} - {$clusterSet1['clusterCode']}"));
        $worksheet->SetCellValue('G4', date_format(date_create($clusterSet1['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G5', date_format(date_create($clusterSet1['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K4', date('F j, Y'));
        $worksheet->SetCellValue('K5', $clusterSet1['weeksToPay'] . ' Weeks');

        foreach ($clusterSet1['gpClients'] as $key1 => $clients1) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient1Start1, $key1 + 1);
            $worksheet->SetCellValue("B" . $rowClient1Start1, strtoupper("{$clients1['clientInfo']['firstName']} {$clients1['clientInfo']['middleInitial']} {$clients1['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient1Start1, "{$clients1['lr']}");
            $worksheet->SetCellValue("D" . $rowClient1Start1, "{$clients1['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient1Start1, "{$clients1['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient1Start1, "{$clients1['wi']}");
            $worksheet->SetCellValue("K" . $rowClient1Start1, round(dateDifference($clusterSet1['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient1Start1++;
        }

        //cluster 2
        $worksheet->SetCellValue('C22', strtoupper($clusterSet2['staffName']));
        $worksheet->SetCellValue('C23', strtoupper("{$clusterSet2['staffCodeNameId']} - {$clusterSet2['clusterCode']}"));
        $worksheet->SetCellValue('G22', date_format(date_create($clusterSet2['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G23', date_format(date_create($clusterSet2['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K22', date('F j, Y'));
        $worksheet->SetCellValue('K23', $clusterSet2['weeksToPay'] . ' Weeks');

        foreach ($clusterSet2['gpClients'] as $key2 => $clients2) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient2Start2, $key2 + 1);
            $worksheet->SetCellValue("B" . $rowClient2Start2, strtoupper("{$clients2['clientInfo']['firstName']} {$clients2['clientInfo']['middleInitial']} {$clients2['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient2Start2, "{$clients2['lr']}");
            $worksheet->SetCellValue("D" . $rowClient2Start2, "{$clients2['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient2Start2, "{$clients2['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient2Start2, "{$clients2['wi']}");
            $worksheet->SetCellValue("K" . $rowClient2Start2, round(dateDifference($clusterSet2['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient2Start2++;
        }
    } else if (count($clusterSet1['gpClients']) <= 15 && count($clusterSet2['gpClients']) <= 15) {
        $rowClient1Start1 = 7;
        $rowClient2Start2 = 30;
        //cluster 1
        $worksheet->SetCellValue('C4', strtoupper($clusterSet1['staffName']));
        $worksheet->SetCellValue('C5', strtoupper("{$clusterSet1['staffCodeNameId']} - {$clusterSet1['clusterCode']}"));
        $worksheet->SetCellValue('G4', date_format(date_create($clusterSet1['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G5', date_format(date_create($clusterSet1['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K4', date('F j, Y'));
        $worksheet->SetCellValue('K5', $clusterSet1['weeksToPay'] . ' Weeks');

        foreach ($clusterSet1['gpClients'] as $key1 => $clients1) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient1Start1, $key1 + 1);
            $worksheet->SetCellValue("B" . $rowClient1Start1, strtoupper("{$clients1['clientInfo']['firstName']} {$clients1['clientInfo']['middleInitial']} {$clients1['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient1Start1, "{$clients1['lr']}");
            $worksheet->SetCellValue("D" . $rowClient1Start1, "{$clients1['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient1Start1, "{$clients1['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient1Start1, "{$clients1['wi']}");
            $worksheet->SetCellValue("K" . $rowClient1Start1, round(dateDifference($clusterSet1['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient1Start1++;
        }

        //cluster 2
        $worksheet->SetCellValue('C27', strtoupper($clusterSet2['staffName']));
        $worksheet->SetCellValue('C28', strtoupper("{$clusterSet2['staffCodeNameId']} - {$clusterSet2['clusterCode']}"));
        $worksheet->SetCellValue('G27', date_format(date_create($clusterSet2['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G28', date_format(date_create($clusterSet2['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K27', date('F j, Y'));
        $worksheet->SetCellValue('K28', $clusterSet2['weeksToPay'] . ' Weeks');

        foreach ($clusterSet2['gpClients'] as $key2 => $clients2) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient2Start2, $key2 + 1);
            $worksheet->SetCellValue("B" . $rowClient2Start2, strtoupper("{$clients2['clientInfo']['firstName']} {$clients2['clientInfo']['middleInitial']} {$clients2['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient2Start2, "{$clients2['lr']}");
            $worksheet->SetCellValue("D" . $rowClient2Start2, "{$clients2['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient2Start2, "{$clients2['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient2Start2, "{$clients2['wi']}");
            $worksheet->SetCellValue("K" . $rowClient2Start2, round(dateDifference($clusterSet2['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient2Start2++;
        }
    } else {

        $rowClient1Start1 = 7;
        $rowClient2Start2 = 34;
        //cluster 1
        $worksheet->SetCellValue('C4', strtoupper($clusterSet1['staffName']));
        $worksheet->SetCellValue('C5', strtoupper("{$clusterSet1['staffCodeNameId']} - {$clusterSet1['clusterCode']}"));
        $worksheet->SetCellValue('G4', date_format(date_create($clusterSet1['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G5', date_format(date_create($clusterSet1['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K4', date('F j, Y'));
        $worksheet->SetCellValue('K5', $clusterSet1['weeksToPay'] . ' Weeks');

        foreach ($clusterSet1['gpClients'] as $key1 => $clients1) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient1Start1, $key1 + 1);
            $worksheet->SetCellValue("B" . $rowClient1Start1, strtoupper("{$clients1['clientInfo']['firstName']} {$clients1['clientInfo']['middleInitial']} {$clients1['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient1Start1, "{$clients1['lr']}");
            $worksheet->SetCellValue("D" . $rowClient1Start1, "{$clients1['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient1Start1, "{$clients1['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient1Start1, "{$clients1['wi']}");
            $worksheet->SetCellValue("K" . $rowClient1Start1, round(dateDifference($clusterSet1['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient1Start1++;
        }

        //cluster 2
        $worksheet->SetCellValue('C31', strtoupper($clusterSet2['staffName']));
        $worksheet->SetCellValue('C32', strtoupper("{$clusterSet2['staffCodeNameId']} - {$clusterSet2['clusterCode']}"));
        $worksheet->SetCellValue('G31', date_format(date_create($clusterSet2['dateOfReleased']), 'F j, Y'));
        $worksheet->SetCellValue('G32', date_format(date_create($clusterSet2['dateOfFirstPayment']), 'F j, Y'));
        $worksheet->SetCellValue('K31', date('F j, Y'));
        $worksheet->SetCellValue('K32', $clusterSet2['weeksToPay'] . ' Weeks');

        foreach ($clusterSet2['gpClients'] as $key2 => $clients2) {
            # code...

            $worksheet->SetCellValue("A" . $rowClient2Start2, $key2 + 1);
            $worksheet->SetCellValue("B" . $rowClient2Start2, strtoupper("{$clients2['clientInfo']['firstName']} {$clients2['clientInfo']['middleInitial']} {$clients2['clientInfo']['lastName']}"));
            $worksheet->SetCellValue("C" . $rowClient2Start2, "{$clients2['lr']}");
            $worksheet->SetCellValue("D" . $rowClient2Start2, "{$clients2['skCum']}");
            $worksheet->SetCellValue("E" . $rowClient2Start2, "{$clients2['pastDue']}");
            $worksheet->SetCellValue("G" . $rowClient2Start2, "{$clients2['wi']}");
            $worksheet->SetCellValue("K" . $rowClient2Start2, round(dateDifference($clusterSet2['dateOfFirstPayment'], date('Y-n-j'), '%a') / 7));
            $rowClient2Start2++;
        }
    }
}


$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
ob_start();
$writer->save('php://output');
$writer_data = ob_get_contents();
ob_end_clean();

header('Cache-Control: max-age=0');

$data =  array(
    'status' => 200,
    'success' => true,
    'filename' => implode(" ", $filename),
    'file' =>  base64_encode($writer_data)
);

exit(json_encode($data));
