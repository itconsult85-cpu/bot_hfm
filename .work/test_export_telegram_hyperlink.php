<?php
require dirname(__DIR__) . '/app/Libraries/SimpleXlsxExporter.php';

$exporter = new \App\Libraries\SimpleXlsxExporter();
$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hfm-link-test.xlsx';
file_put_contents($path, $exporter->create(
    ['ID Telegram'],
    [['123456789']],
    ['A2' => 'https://t.me/Test_user']
));

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    throw new RuntimeException('Cannot open generated workbook.');
}
$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$relationships = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
$styles = $zip->getFromName('xl/styles.xml');
$zip->close();
unlink($path);

if (
    strpos($sheet, '<hyperlink ref="A2" r:id="rId1"/>') === false
    || strpos($relationships, 'Target="https://t.me/Test_user"') === false
    || strpos($styles, 'FF0563C1') === false
) {
    throw new RuntimeException('Hyperlink XLSX verification failed.');
}

echo "XLSX Telegram username hyperlink verification passed.\n";
