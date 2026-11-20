<?php
$path = 'D:\\db_hfm_bot (10).sql';
$text = file_get_contents($path);
if ($text === false) {
    throw new RuntimeException('Cannot read supplied SQL dump.');
}

function extractRows(string $text, string $table): array
{
    $rows = [];
    $marker = 'INSERT INTO `' . $table . '`';
    $offset = 0;
    while (($start = strpos($text, $marker, $offset)) !== false) {
        $i = $start;
        $quote = null;
        $escaped = false;
        $length = strlen($text);
        for (; $i < $length; $i++) {
            $char = $text[$i];
            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === "\\") {
                    $escaped = true;
                } elseif ($char === $quote) {
                    if ($i + 1 < $length && $text[$i + 1] === $quote) {
                        $i++;
                    } else {
                        $quote = null;
                    }
                }
            } elseif (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;
            } elseif ($char === ';') {
                break;
            }
        }
        $statement = substr($text, $start, $i - $start);
        $offset = $i + 1;
        if (!preg_match('/INSERT INTO `[^`]+`\s*\((.*?)\)\s*VALUES\s*/s', $statement, $match, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $columns = array_map(static fn ($v) => trim($v, " `\t\r\n"), explode(',', $match[1][0]));
        $values = substr($statement, $match[0][1] + strlen($match[0][0]));
        $j = 0;
        $n = strlen($values);
        while ($j < $n) {
            while ($j < $n && str_contains(" ,\r\n\t", $values[$j])) $j++;
            if ($j >= $n || $values[$j] !== '(') break;
            $j++;
            $cellStart = $j;
            $depth = 1;
            $quote = null;
            $escaped = false;
            $cells = [];
            for (; $j < $n && $depth > 0; $j++) {
                $char = $values[$j];
                if ($quote !== null) {
                    if ($escaped) $escaped = false;
                    elseif ($char === "\\") $escaped = true;
                    elseif ($char === $quote) {
                        if ($j + 1 < $n && $values[$j + 1] === $quote) $j++;
                        else $quote = null;
                    }
                } elseif ($char === "'") {
                    $quote = $char;
                } elseif ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) $cells[] = trim(substr($values, $cellStart, $j - $cellStart));
                } elseif ($char === ',' && $depth === 1) {
                    $cells[] = trim(substr($values, $cellStart, $j - $cellStart));
                    $cellStart = $j + 1;
                }
            }
            if ($depth !== 0) break;
            if (count($cells) === count($columns)) {
                $row = [];
                foreach ($columns as $k => $column) {
                    $value = $cells[$k];
                    if (strtoupper($value) === 'NULL') $value = null;
                    elseif (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
                        $value = substr($value, 1, -1);
                        $value = str_replace(["\\'", "\\\"", "\\\\", "''"], ["'", '"', "\\", "'"], $value);
                    }
                    $row[$column] = $value;
                }
                $rows[] = $row;
            }
            while ($j < $n && str_contains(" \r\n\t", $values[$j])) $j++;
            if ($j < $n && $values[$j] === ',') $j++;
        }
    }
    return $rows;
}

$members = extractRows($text, 'telegram_group_members');
$logs = extractRows($text, 'chat_logs_tele');
$memberIds = [];
$idsWithUsernames = [];
$namedRows = 0;
foreach ($members as $row) {
    $id = trim((string) ($row['telegram_id'] ?? ''));
    if ($id !== '') $memberIds[$id] = true;
    $username = trim((string) ($row['telegram_username'] ?? ''));
    if ($username !== '') {
        $namedRows++;
        if ($id !== '') $idsWithUsernames[$id] = true;
    }
}
$logIds = [];
$userLogIds = [];
$senderCounts = [];
foreach ($logs as $row) {
    $sender = trim((string) ($row['sender'] ?? '(null)'));
    $senderCounts[$sender] = ($senderCounts[$sender] ?? 0) + 1;
    $id = trim((string) ($row['phone_number'] ?? ''));
    if ($id !== '' && ctype_digit($id)) {
        $logIds[$id] = true;
        if ($sender === 'user') $userLogIds[$id] = true;
    }
}
$intersect = static fn (array $left, array $right): int => count(array_intersect_key($left, $right));
$collisions = 0;
foreach ($memberIds as $id => $_) if (isset($idsWithUsernames[$id])) $collisions++;
printf("Rows telegram_group_members parsed: %d; distinct Telegram IDs: %d; rows with username: %d; distinct IDs with username: %d\n", count($members), count($memberIds), $namedRows, count($idsWithUsernames));
printf("Rows chat_logs_tele parsed: %d; distinct numeric IDs (all senders): %d; distinct numeric IDs (sender=user): %d\n", count($logs), count($logIds), count($userLogIds));
printf("IDs in chat logs matching membership IDs: %d; matching IDs with username: %d\n", $intersect($logIds, $memberIds), $intersect($logIds, $idsWithUsernames));
printf("IDs from sender=user matching membership IDs: %d; matching IDs with username: %d\n", $intersect($userLogIds, $memberIds), $intersect($userLogIds, $idsWithUsernames));
printf("Sender categories/counts: %s\n", json_encode($senderCounts));
