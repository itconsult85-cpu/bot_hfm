<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$content = (new App\Libraries\SimpleXlsxExporter())->create(
    ['Nama', 'ID'],
    [
        ['Budi & Siti', '00123'],
        ['Dewi <Admin>', '456'],
    ],
);

$temporaryFile = tempnam(sys_get_temp_dir(), 'xlsx-check-');
if ($temporaryFile === false || file_put_contents($temporaryFile, $content) === false) {
    throw new RuntimeException('Unable to save XLSX test output.');
}

$archive = new ZipArchive();
if ($archive->open($temporaryFile) !== true) {
    throw new RuntimeException('Generated file is not a valid ZIP archive.');
}

$requiredEntries = [
    '[Content_Types].xml',
    '_rels/.rels',
    'xl/workbook.xml',
    'xl/_rels/workbook.xml.rels',
    'xl/worksheets/sheet1.xml',
];
foreach ($requiredEntries as $entry) {
    if ($archive->locateName($entry) === false) {
        throw new RuntimeException('Missing XLSX entry: ' . $entry);
    }
}

$sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
if ($sheet === false || simplexml_load_string($sheet) === false) {
    throw new RuntimeException('Worksheet XML is invalid.');
}
if (!str_contains($sheet, 'Budi &amp; Siti') || !str_contains($sheet, 'Dewi &lt;Admin&gt;')) {
    throw new RuntimeException('Worksheet text was not XML-escaped as expected.');
}

$archive->close();
unlink($temporaryFile);
echo "XLSX valid; all required package entries present; worksheet XML valid; special characters escaped.\n";
