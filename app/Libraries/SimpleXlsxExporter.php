<?php

namespace App\Libraries;

use RuntimeException;
use ZipArchive;

/**
 * Builds a basic, standards-compliant XLSX workbook using PHP's ZIP extension.
 */
class SimpleXlsxExporter
{
    /**
     * @param list<string>              $headers
     * @param list<array<int, mixed>>   $rows
     * @param array<string, string>     $hyperlinks Map of cell references (e.g. D2) to external URLs.
     */
    public function create(array $headers, array $rows, array $hyperlinks = []): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZIP extension is not available.');
        }

        $sheetRows = [$headers, ...$rows];
        $xmlRows = [];
        $worksheetLinks = [];
        $relationshipLinks = [];
        foreach ($sheetRows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1) . ($rowIndex + 1);
                $text = $this->escapeXml((string) ($value ?? ''));
                $cellStyle = '';
                if (isset($hyperlinks[$reference]) && $this->isSupportedHyperlink($hyperlinks[$reference])) {
                    $relationshipId = 'rId' . (count($relationshipLinks) + 1);
                    $worksheetLinks[] = '<hyperlink ref="' . $reference . '" r:id="' . $relationshipId . '"/>';
                    $relationshipLinks[$relationshipId] = $hyperlinks[$reference];
                    $cellStyle = ' s="1"';
                }
                $cells[] = '<c r="' . $reference . '"' . $cellStyle . ' t="inlineStr"><is><t xml:space="preserve">'
                    . $text . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        $lastColumn = $this->columnName(max(1, count($headers)));
        $lastRow = max(1, count($sheetRows));
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Data User Terkick" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>'
                . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
                . (empty($worksheetLinks) ? '' : '<hyperlinks>' . implode('', $worksheetLinks) . '</hyperlinks>')
                . '</worksheet>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
                . '<font><sz val="11"/><color rgb="FF0563C1"/><u/><name val="Calibri"/></font></fonts>'
                . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
                . '<fill><patternFill patternType="gray125"/></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
                . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
                . '</styleSheet>',
        ];

        if ($relationshipLinks !== []) {
            $files['xl/worksheets/_rels/sheet1.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . implode('', array_map(fn (string $relationshipId, string $url): string =>
                    '<Relationship Id="' . $relationshipId . '" '
                    . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" '
                    . 'Target="' . $this->escapeXml($url) . '" TargetMode="External"/>',
                    array_keys($relationshipLinks), array_values($relationshipLinks)))
                . '</Relationships>';
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'hfm-xlsx-');
        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create a temporary XLSX file.');
        }

        try {
            $archive = new ZipArchive();
            $opened = $archive->open($temporaryFile, ZipArchive::OVERWRITE);
            if ($opened !== true) {
                throw new RuntimeException('Unable to open the temporary XLSX archive.');
            }

            foreach ($files as $name => $contents) {
                if (!$archive->addFromString($name, $contents)) {
                    $archive->close();
                    throw new RuntimeException('Unable to write XLSX archive entry: ' . $name);
                }
            }

            if (!$archive->close()) {
                throw new RuntimeException('Unable to finalize the XLSX archive.');
            }

            $content = file_get_contents($temporaryFile);
            if ($content === false) {
                throw new RuntimeException('Unable to read the generated XLSX archive.');
            }

            return $content;
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(($number % 26) + 65) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        $validXml = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
        if ($validXml === null) {
            $validXml = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return htmlspecialchars($validXml, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function isSupportedHyperlink(string $url): bool
    {
        return preg_match('/^tg:\/\/user\?id=[1-9][0-9]*$/', $url) === 1
            || filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
