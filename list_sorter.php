<?php
// list_sorter.php - Сортировщик списков на PHP (CLI + веб)
// CLI: php list_sorter.php --list "5,3,8,1" --compare
// Веб: откройте как HTML

// ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
function bubbleSort($arr) {
    $result = $arr;
    $n = count($result);
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($result[$j] > $result[$j + 1]) {
                $tmp = $result[$j];
                $result[$j] = $result[$j + 1];
                $result[$j + 1] = $tmp;
            }
        }
    }
    return $result;
}

function quickSort($arr) {
    if (count($arr) <= 1) return $arr;
    $pivot = $arr[floor(count($arr) / 2)];
    $left = array_filter($arr, function($x) use ($pivot) { return $x < $pivot; });
    $middle = array_filter($arr, function($x) use ($pivot) { return $x == $pivot; });
    $right = array_filter($arr, function($x) use ($pivot) { return $x > $pivot; });
    return array_merge(quickSort(array_values($left)), array_values($middle), quickSort(array_values($right)));
}

function mergeSort($arr) {
    if (count($arr) <= 1) return $arr;
    $mid = floor(count($arr) / 2);
    $left = mergeSort(array_slice($arr, 0, $mid));
    $right = mergeSort(array_slice($arr, $mid));
    return merge($left, $right);
}

function merge($left, $right) {
    $result = [];
    $i = $j = 0;
    while ($i < count($left) && $j < count($right)) {
        if ($left[$i] <= $right[$j]) {
            $result[] = $left[$i++];
        } else {
            $result[] = $right[$j++];
        }
    }
    return array_merge($result, array_slice($left, $i), array_slice($right, $j));
}

function selectionSort($arr) {
    $result = $arr;
    $n = count($result);
    for ($i = 0; $i < $n; $i++) {
        $minIdx = $i;
        for ($j = $i + 1; $j < $n; $j++) {
            if ($result[$j] < $result[$minIdx]) $minIdx = $j;
        }
        $tmp = $result[$i];
        $result[$i] = $result[$minIdx];
        $result[$minIdx] = $tmp;
    }
    return $result;
}

function insertionSort($arr) {
    $result = $arr;
    $n = count($result);
    for ($i = 1; $i < $n; $i++) {
        $key = $result[$i];
        $j = $i - 1;
        while ($j >= 0 && $result[$j] > $key) {
            $result[$j + 1] = $result[$j];
            $j--;
        }
        $result[$j + 1] = $key;
    }
    return $result;
}

function builtinSort($arr) {
    $result = $arr;
    sort($result);
    return $result;
}

$algorithms = [
    '1' => ['name' => 'Пузырьковая', 'func' => 'bubbleSort'],
    '2' => ['name' => 'Быстрая', 'func' => 'quickSort'],
    '3' => ['name' => 'Слиянием', 'func' => 'mergeSort'],
    '4' => ['name' => 'Стандартная', 'func' => 'builtinSort'],
    '5' => ['name' => 'Выбором', 'func' => 'selectionSort'],
    '6' => ['name' => 'Вставками', 'func' => 'insertionSort'],
];

function measureTime($func, $data) {
    $arr = $data;
    $start = microtime(true);
    $result = $func($arr);
    $elapsed = microtime(true) - $start;
    return [$result, $elapsed];
}

function generateRandomList($size, $minVal = 1, $maxVal = 100) {
    $result = [];
    for ($i = 0; $i < $size; $i++) {
        $result[] = rand($minVal, $maxVal);
    }
    return $result;
}

function printTable($results, $topN = 20) {
    if (empty($results)) {
        echo "Нет данных.\n";
        return;
    }
    $maxTime = max(array_column($results, 'time'));
    echo "\n" . str_repeat("=", 70) . "\n";
    echo str_pad("РЕЗУЛЬТАТЫ СОРТИРОВКИ", 70, " ", STR_PAD_BOTH) . "\n";
    echo str_repeat("=", 70) . "\n";
    foreach ($results as $name => $data) {
        $elapsed = $data['time'];
        $barLen = $maxTime > 0 ? (int)(($elapsed / $maxTime) * 40) : 0;
        $bar = str_repeat("█", $barLen) . str_repeat("░", 40 - $barLen);
        printf("%-15s %.6f сек.  %s\n", $name, $elapsed, $bar);
    }
    echo str_repeat("=", 70) . "\n";
    $first = reset($results);
    $sorted = $first['sorted'];
    if (count($sorted) <= $topN) {
        echo "Отсортированный список: " . implode(", ", $sorted) . "\n";
    } else {
        echo "Отсортированный список (первые $topN): " . implode(", ", array_slice($sorted, 0, $topN)) . "\n";
    }
}

function exportCSV($results, $filename) {
    $f = fopen($filename, 'w');
    fputcsv($f, ['Алгоритм', 'Время (сек)', 'Размер списка']);
    $size = count(reset($results)['sorted']);
    foreach ($results as $name => $data) {
        fputcsv($f, [$name, $data['time'], $size]);
    }
    fclose($f);
}

function exportJSON($results, $filename) {
    $data = [];
    foreach ($results as $name => $info) {
        $data[$name] = [
            'sorted' => $info['sorted'],
            'time' => $info['time'],
            'size' => count($info['sorted'])
        ];
    }
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getInput($prompt) {
    echo $prompt;
    return trim(fgets(STDIN));
}

if (php_sapi_name() === 'cli') {
    $options = getopt("", ["list:", "random:", "algorithm:", "export-csv:", "export-json:", "compare"]);
    if (isset($options['list']) || isset($options['random'])) {
        $data = [];
        if (isset($options['list'])) {
            $parts = explode(',', $options['list']);
            foreach ($parts as $p) {
                if (is_numeric(trim($p))) $data[] = (int)trim($p);
            }
        } elseif (isset($options['random'])) {
            $size = (int)$options['random'];
            $data = generateRandomList($size);
        }
        if (empty($data)) { echo "Список пуст.\n"; exit; }
        $results = [];
        $algorithm = $options['algorithm'] ?? null;
        $compare = isset($options['compare']);
        if ($compare || $algorithm == 'all') {
            foreach ($algorithms as $alg) {
                list($sorted, $elapsed) = measureTime($alg['func'], $data);
                $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
            }
        } elseif (isset($algorithms[$algorithm])) {
            $alg = $algorithms[$algorithm];
            list($sorted, $elapsed) = measureTime($alg['func'], $data);
            $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
        } else {
            echo "Укажите --algorithm или --compare\n";
            exit;
        }
        printTable($results);
        if (isset($options['export-csv'])) {
            exportCSV($results, $options['export-csv']);
            echo "Экспортировано в {$options['export-csv']}\n";
        }
        if (isset($options['export-json'])) {
            exportJSON($results, $options['export-json']);
            echo "Экспортировано в {$options['export-json']}\n";
        }
    } else {
        // Интерактивный режим
        echo "📊 СОРТИРОВЩИК СПИСКОВ\n";
        while (true) {
            echo "\nВыберите действие:\n";
            echo "1. Сортировать введённый список\n";
            echo "2. Сгенерировать случайный список\n";
            echo "3. Сравнить все алгоритмы\n";
            echo "0. Выход\n";
            $choice = getInput("Ваш выбор: ");
            if ($choice == '0') break;
            elseif ($choice == '1') {
                $input = getInput("Введите числа через пробел: ");
                $data = array_filter(array_map('intval', explode(' ', $input)));
                if (empty($data)) { echo "Список пуст.\n"; continue; }
                $results = [];
                echo "\nВыберите алгоритм (или all для всех):\n";
                foreach ($algorithms as $key => $alg) {
                    echo "$key. {$alg['name']}\n";
                }
                $algoChoice = getInput("Ваш выбор: ");
                if ($algoChoice == 'all') {
                    foreach ($algorithms as $alg) {
                        list($sorted, $elapsed) = measureTime($alg['func'], $data);
                        $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                    }
                } elseif (isset($algorithms[$algoChoice])) {
                    $alg = $algorithms[$algoChoice];
                    list($sorted, $elapsed) = measureTime($alg['func'], $data);
                    $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                } else {
                    echo "Неверный выбор.\n";
                    continue;
                }
                printTable($results);
                $export = getInput("Экспортировать результаты? (y/n): ");
                if (strtolower($export) == 'y') {
                    $fmt = getInput("Формат (csv/json): ");
                    $filename = getInput("Имя файла: ");
                    if (empty($filename)) $filename = "results.$fmt";
                    if ($fmt == 'csv') exportCSV($results, $filename);
                    else exportJSON($results, $filename);
                    echo "Экспортировано в $filename\n";
                }
            } elseif ($choice == '2') {
                $size = (int)getInput("Размер списка: ");
                $minVal = (int)getInput("Минимальное значение: ");
                $maxVal = (int)getInput("Максимальное значение: ");
                $data = generateRandomList($size, $minVal, $maxVal);
                echo "Сгенерированный список: " . implode(", ", $data) . "\n";
                $results = [];
                echo "\nВыберите алгоритм (или all для всех):\n";
                foreach ($algorithms as $key => $alg) {
                    echo "$key. {$alg['name']}\n";
                }
                $algoChoice = getInput("Ваш выбор: ");
                if ($algoChoice == 'all') {
                    foreach ($algorithms as $alg) {
                        list($sorted, $elapsed) = measureTime($alg['func'], $data);
                        $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                    }
                } elseif (isset($algorithms[$algoChoice])) {
                    $alg = $algorithms[$algoChoice];
                    list($sorted, $elapsed) = measureTime($alg['func'], $data);
                    $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                } else {
                    echo "Неверный выбор.\n";
                    continue;
                }
                printTable($results);
            } elseif ($choice == '3') {
                $input = getInput("Введите числа через пробел (или оставьте пустым для случайных): ");
                if (empty($input)) {
                    $size = (int)getInput("Размер случайного списка: ");
                    $data = generateRandomList($size);
                    echo "Сгенерированный список: " . implode(", ", $data) . "\n";
                } else {
                    $data = array_filter(array_map('intval', explode(' ', $input)));
                }
                if (empty($data)) { echo "Список пуст.\n"; continue; }
                $results = [];
                foreach ($algorithms as $alg) {
                    list($sorted, $elapsed) = measureTime($alg['func'], $data);
                    $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                }
                printTable($results);
                $export = getInput("Экспортировать результаты? (y/n): ");
                if (strtolower($export) == 'y') {
                    $fmt = getInput("Формат (csv/json): ");
                    $filename = getInput("Имя файла: ");
                    if (empty($filename)) $filename = "results.$fmt";
                    if ($fmt == 'csv') exportCSV($results, $filename);
                    else exportJSON($results, $filename);
                    echo "Экспортировано в $filename\n";
                }
            } else {
                echo "Неверный выбор.\n";
            }
        }
    }
    exit;
}

// ========== ВЕБ-ИНТЕРФЕЙС ==========
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Сортировщик списков (PHP)</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fb; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 120px; }
        input, select, button { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #3498db; color: white; border: none; cursor: pointer; padding: 6px 20px; }
        button:hover { background: #2980b9; }
        .result { background: #ecf0f1; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .table { font-family: monospace; white-space: pre; }
    </style>
</head>
<body>
<div class="container">
    <h1>📊 Сортировщик списков (PHP)</h1>
    <form method="GET">
        <div class="form-group">
            <label>Список чисел:</label>
            <input type="text" name="list" placeholder="5,3,8,1,9" value="<?= isset($_GET['list']) ? htmlspecialchars($_GET['list']) : '' ?>">
        </div>
        <div class="form-group">
            <label>Алгоритм:</label>
            <select name="algorithm">
                <option value="all" <?= isset($_GET['algorithm']) && $_GET['algorithm'] == 'all' ? 'selected' : '' ?>>Все</option>
                <?php foreach ($algorithms as $key => $alg): ?>
                    <option value="<?= $key ?>" <?= isset($_GET['algorithm']) && $_GET['algorithm'] == $key ? 'selected' : '' ?>><?= $alg['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Сравнить все:</label>
            <input type="checkbox" name="compare" <?= isset($_GET['compare']) ? 'checked' : '' ?>>
        </div>
        <button type="submit">Сортировать</button>
        <a href="?export_csv=1&<?= http_build_query($_GET) ?>">📥 CSV</a>
        <a href="?export_json=1&<?= http_build_query($_GET) ?>">📥 JSON</a>
    </form>

    <?php if (isset($_GET['export_csv']) || isset($_GET['export_json'])): 
        $list = $_GET['list'] ?? '';
        if ($list) {
            $data = array_filter(array_map('intval', explode(',', $list)));
            if ($data) {
                $results = [];
                $algorithm = $_GET['algorithm'] ?? null;
                $compare = isset($_GET['compare']);
                if ($compare || $algorithm == 'all') {
                    foreach ($algorithms as $alg) {
                        list($sorted, $elapsed) = measureTime($alg['func'], $data);
                        $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                    }
                } elseif (isset($algorithms[$algorithm])) {
                    $alg = $algorithms[$algorithm];
                    list($sorted, $elapsed) = measureTime($alg['func'], $data);
                    $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                }
                if ($results) {
                    if (isset($_GET['export_csv'])) {
                        header('Content-Type: text/csv');
                        header('Content-Disposition: attachment; filename="results.csv"');
                        exportCSV($results, 'php://output');
                        exit;
                    } else {
                        header('Content-Type: application/json');
                        $jsonData = [];
                        foreach ($results as $name => $info) {
                            $jsonData[$name] = ['sorted' => $info['sorted'], 'time' => $info['time'], 'size' => count($info['sorted'])];
                        }
                        echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
            }
        }
    endif; ?>

    <?php if (isset($_GET['list']) && !empty($_GET['list'])): 
        $data = array_filter(array_map('intval', explode(',', $_GET['list'])));
        if ($data):
            $results = [];
            $algorithm = $_GET['algorithm'] ?? null;
            $compare = isset($_GET['compare']);
            if ($compare || $algorithm == 'all') {
                foreach ($algorithms as $alg) {
                    list($sorted, $elapsed) = measureTime($alg['func'], $data);
                    $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
                }
            } elseif (isset($algorithms[$algorithm])) {
                $alg = $algorithms[$algorithm];
                list($sorted, $elapsed) = measureTime($alg['func'], $data);
                $results[$alg['name']] = ['sorted' => $sorted, 'time' => $elapsed];
            }
            if ($results):
    ?>
        <div class="result">
            <p><strong>Исходный список:</strong> <?= implode(', ', $data) ?></p>
            <div class="table">
                <?php
                $maxTime = max(array_column($results, 'time'));
                echo str_repeat("=", 70) . "\n";
                echo str_pad("РЕЗУЛЬТАТЫ СОРТИРОВКИ", 70, " ", STR_PAD_BOTH) . "\n";
                echo str_repeat("=", 70) . "\n";
                foreach ($results as $name => $info) {
                    $elapsed = $info['time'];
                    $barLen = $maxTime > 0 ? (int)(($elapsed / $maxTime) * 40) : 0;
                    $bar = str_repeat("█", $barLen) . str_repeat("░", 40 - $barLen);
                    printf("%-15s %.6f сек.  %s\n", $name, $elapsed, $bar);
                }
                echo str_repeat("=", 70) . "\n";
                $first = reset($results);
                $sorted = $first['sorted'];
                if (count($sorted) <= 20) {
                    echo "Отсортированный список: " . implode(", ", $sorted) . "\n";
                } else {
                    echo "Отсортированный список (первые 20): " . implode(", ", array_slice($sorted, 0, 20)) . "\n";
                }
                ?>
            </div>
        </div>
    <?php endif; endif; endif; ?>
</div>
</body>
</html>
