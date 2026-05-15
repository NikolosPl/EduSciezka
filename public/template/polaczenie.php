<?php

require_once __DIR__ . '/security.php';

$db_host = edusciezka_env('EDUSCIEZKA_DB_HOST');
$db_user = edusciezka_env('EDUSCIEZKA_DB_USER');
$db_pass = edusciezka_env('EDUSCIEZKA_DB_PASS');
$db_name = edusciezka_env('EDUSCIEZKA_DB_NAME');

if ($db_host === null || $db_user === null || $db_pass === null || $db_name === null) {
    die('Brak konfiguracji bazy danych w pliku .env.');
}

$polaczenie = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$polaczenie) {
    die("Blad polaczenia: " . mysqli_connect_error());
}

mysqli_set_charset($polaczenie, "utf8mb4");

function edusciezka_table_exists($polaczenie, $table)
{
    $table = mysqli_real_escape_string($polaczenie, $table);
    $wynik = mysqli_query($polaczenie, "SHOW TABLES LIKE '$table'");
    return $wynik && mysqli_num_rows($wynik) > 0;
}

function edusciezka_column_exists($polaczenie, $table, $column)
{
    $table = mysqli_real_escape_string($polaczenie, $table);
    $column = mysqli_real_escape_string($polaczenie, $column);
    $wynik = mysqli_query($polaczenie, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $wynik && mysqli_num_rows($wynik) > 0;
}

function edusciezka_index_exists($polaczenie, $table, $index)
{
    $table = mysqli_real_escape_string($polaczenie, $table);
    $index = mysqli_real_escape_string($polaczenie, $index);
    $wynik = mysqli_query($polaczenie, "SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return $wynik && mysqli_num_rows($wynik) > 0;
}

function edusciezka_constraint_exists($polaczenie, $table, $constraint)
{
    $table = mysqli_real_escape_string($polaczenie, $table);
    $constraint = mysqli_real_escape_string($polaczenie, $constraint);
    $sql = "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '$table'
              AND CONSTRAINT_NAME = '$constraint'
            LIMIT 1";
    $wynik = mysqli_query($polaczenie, $sql);
    return $wynik && mysqli_num_rows($wynik) > 0;
}

function edusciezka_safe_query($polaczenie, $sql)
{
    try {
        return mysqli_query($polaczenie, $sql);
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function edusciezka_index_columns($clause)
{
    if (!preg_match('/\((.*)\)/', $clause, $matches)) {
        return array();
    }

    $inside = $matches[1];
    preg_match_all('/`([^`]+)`|([A-Za-z0-9_]+)/', $inside, $column_matches, PREG_SET_ORDER);

    $columns = array();
    foreach ($column_matches as $match) {
        $column = isset($match[1]) && $match[1] !== '' ? $match[1] : $match[2];
        if ($column === '' || strtoupper($column) === 'ASC' || strtoupper($column) === 'DESC') {
            continue;
        }
        $columns[] = $column;
    }

    return array_values(array_unique($columns));
}

function edusciezka_split_sql_statements($sql)
{
    $statements = array();
    $buffer = '';
    $in_single = false;
    $in_double = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && $prev !== '\\' && !$in_double) {
            $in_single = !$in_single;
        } elseif ($char === '"' && $prev !== '\\' && !$in_single) {
            $in_double = !$in_double;
        }

        if ($char === ';' && !$in_single && !$in_double) {
            $statements[] = trim($buffer);
            $buffer = '';
        } else {
            $buffer .= $char;
        }
    }

    $buffer = trim($buffer);
    if ($buffer !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}

function edusciezka_split_alter_clauses($definition)
{
    $clauses = array();
    $buffer = '';
    $depth = 0;
    $in_single = false;
    $in_double = false;
    $length = strlen($definition);

    for ($i = 0; $i < $length; $i++) {
        $char = $definition[$i];
        $prev = $i > 0 ? $definition[$i - 1] : '';

        if ($char === "'" && $prev !== '\\' && !$in_double) {
            $in_single = !$in_single;
        } elseif ($char === '"' && $prev !== '\\' && !$in_single) {
            $in_double = !$in_double;
        }

        if (!$in_single && !$in_double) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $clauses[] = trim($buffer);
                $buffer = '';
                continue;
            }
        }

        $buffer .= $char;
    }

    $buffer = trim($buffer);
    if ($buffer !== '') {
        $clauses[] = $buffer;
    }

    return $clauses;
}

function edusciezka_apply_schema_sync($polaczenie, $schema_file)
{
    if (!is_file($schema_file)) {
        return;
    }

    edusciezka_safe_query($polaczenie, "CREATE TABLE IF NOT EXISTS `system_meta` (
        `klucz` varchar(100) NOT NULL,
        `wartosc` text DEFAULT NULL,
        `zaktualizowano_o` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`klucz`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (edusciezka_table_exists($polaczenie, 'zadania') && !edusciezka_column_exists($polaczenie, 'zadania', 'data_start')) {
        edusciezka_safe_query($polaczenie, "ALTER TABLE `zadania` ADD COLUMN `data_start` date DEFAULT NULL AFTER `opis`");
    }

    $today = date('Y-m-d');
    $wynik = mysqli_query($polaczenie, "SELECT `wartosc` FROM `system_meta` WHERE `klucz` = 'schema_sync_last_run' LIMIT 1");
    if ($wynik) {
        while ($row = mysqli_fetch_assoc($wynik)) {
            $last_run = (string) $row['wartosc'];
        }
    }

    if (isset($last_run) && $last_run === $today) {
        return;
    }

    $sql = file_get_contents($schema_file);
    if ($sql === false) {
        return;
    }

    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = edusciezka_split_sql_statements($sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || substr($statement, 0, 2) === '--') {
            continue;
        }

        if (preg_match('/^CREATE TABLE\s+`?([^`\s]+)`?/i', $statement, $matches)) {
            $table = $matches[1];
            if (!edusciezka_table_exists($polaczenie, $table)) {
                edusciezka_safe_query($polaczenie, $statement);
            }
            continue;
        }

        if (preg_match('/^ALTER TABLE\s+`?([^`\s]+)`?\s+(.*)$/is', $statement, $matches)) {
            $table = $matches[1];
            $definition = trim(rtrim($matches[2], ';'));
            $clauses = edusciezka_split_alter_clauses($definition);

            foreach ($clauses as $clause) {
                if ($clause === '') {
                    continue;
                }

                if (preg_match('/^ADD COLUMN\s+`?([^`\s]+)`?/i', $clause, $column_match)) {
                    if (!edusciezka_column_exists($polaczenie, $table, $column_match[1])) {
                        edusciezka_safe_query($polaczenie, "ALTER TABLE `$table` $clause");
                    }
                    continue;
                }

                if (preg_match('/^ADD PRIMARY KEY/i', $clause)) {
                    if (!edusciezka_index_exists($polaczenie, $table, 'PRIMARY')) {
                        edusciezka_safe_query($polaczenie, "ALTER TABLE `$table` $clause");
                    }
                    continue;
                }

                if (preg_match('/^ADD (?:UNIQUE KEY|KEY|INDEX)\s+`?([^`\s(]+)`?/i', $clause, $index_match)) {
                    $index_columns = edusciezka_index_columns($clause);
                    $missing_columns = false;
                    foreach ($index_columns as $index_column) {
                        if (!edusciezka_column_exists($polaczenie, $table, $index_column)) {
                            $missing_columns = true;
                            break;
                        }
                    }

                    if (!$missing_columns && !edusciezka_index_exists($polaczenie, $table, $index_match[1])) {
                        edusciezka_safe_query($polaczenie, "ALTER TABLE `$table` $clause");
                    }
                    continue;
                }

                if (preg_match('/^ADD CONSTRAINT\s+`?([^`\s]+)`?/i', $clause, $constraint_match)) {
                    if (!edusciezka_constraint_exists($polaczenie, $table, $constraint_match[1])) {
                        edusciezka_safe_query($polaczenie, "ALTER TABLE `$table` $clause");
                    }
                    continue;
                }

                edusciezka_safe_query($polaczenie, "ALTER TABLE `$table` $clause");
            }

            continue;
        }
    }

    $today_esc = mysqli_real_escape_string($polaczenie, $today);
    edusciezka_safe_query($polaczenie, "INSERT INTO `system_meta` (`klucz`, `wartosc`) VALUES ('schema_sync_last_run', '$today_esc') ON DUPLICATE KEY UPDATE `wartosc` = VALUES(`wartosc`)");
}

edusciezka_apply_schema_sync($polaczenie, dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'baza.sql');
?>