#!/usr/bin/env node
/**
 * list_sorter.js - Сортировщик списков на JavaScript (Node.js CLI + веб)
 * CLI: node list_sorter.js --list 5 3 8 1 --compare
 * Веб: откройте как HTML
 */
const fs = require('fs');
const readline = require('readline');
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

// ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
function bubbleSort(arr) {
    const result = [...arr];
    const n = result.length;
    for (let i = 0; i < n; i++) {
        for (let j = 0; j < n - i - 1; j++) {
            if (result[j] > result[j + 1]) {
                [result[j], result[j + 1]] = [result[j + 1], result[j]];
            }
        }
    }
    return result;
}

function quickSort(arr) {
    if (arr.length <= 1) return arr;
    const pivot = arr[Math.floor(arr.length / 2)];
    const left = arr.filter(x => x < pivot);
    const middle = arr.filter(x => x === pivot);
    const right = arr.filter(x => x > pivot);
    return [...quickSort(left), ...middle, ...quickSort(right)];
}

function mergeSort(arr) {
    if (arr.length <= 1) return arr;
    const mid = Math.floor(arr.length / 2);
    const left = mergeSort(arr.slice(0, mid));
    const right = mergeSort(arr.slice(mid));
    return merge(left, right);
}

function merge(left, right) {
    const result = [];
    let i = 0, j = 0;
    while (i < left.length && j < right.length) {
        if (left[i] <= right[j]) {
            result.push(left[i++]);
        } else {
            result.push(right[j++]);
        }
    }
    return [...result, ...left.slice(i), ...right.slice(j)];
}

function selectionSort(arr) {
    const result = [...arr];
    const n = result.length;
    for (let i = 0; i < n; i++) {
        let minIdx = i;
        for (let j = i + 1; j < n; j++) {
            if (result[j] < result[minIdx]) minIdx = j;
        }
        [result[i], result[minIdx]] = [result[minIdx], result[i]];
    }
    return result;
}

function insertionSort(arr) {
    const result = [...arr];
    for (let i = 1; i < result.length; i++) {
        const key = result[i];
        let j = i - 1;
        while (j >= 0 && result[j] > key) {
            result[j + 1] = result[j];
            j--;
        }
        result[j + 1] = key;
    }
    return result;
}

function builtinSort(arr) {
    return [...arr].sort((a, b) => a - b);
}

const ALGORITHMS = {
    '1': { name: 'Пузырьковая', func: bubbleSort },
    '2': { name: 'Быстрая', func: quickSort },
    '3': { name: 'Слиянием', func: mergeSort },
    '4': { name: 'Стандартная', func: builtinSort },
    '5': { name: 'Выбором', func: selectionSort },
    '6': { name: 'Вставками', func: insertionSort },
};

function measureTime(func, data) {
    const arr = [...data];
    const start = performance.now();
    const result = func(arr);
    const elapsed = (performance.now() - start) / 1000;
    return { result, elapsed };
}

function generateRandomList(size, minVal = 1, maxVal = 100) {
    return Array.from({ length: size }, () => Math.floor(Math.random() * (maxVal - minVal + 1)) + minVal);
}

function printTable(results, topN = 20) {
    if (!Object.keys(results).length) {
        console.log('Нет данных.');
        return;
    }
    const maxTime = Math.max(...Object.values(results).map(r => r.elapsed));
    console.log('\n' + '='.repeat(70));
    console.log('РЕЗУЛЬТАТЫ СОРТИРОВКИ'.padStart(35).padEnd(70));
    console.log('='.repeat(70));
    for (const [name, { elapsed }] of Object.entries(results)) {
        const barLen = maxTime > 0 ? Math.floor((elapsed / maxTime) * 40) : 0;
        const bar = '█'.repeat(barLen) + '░'.repeat(40 - barLen);
        console.log(`${name.padEnd(15)} ${elapsed.toFixed(6)} сек.  ${bar}`);
    }
    console.log('='.repeat(70));
    const firstResult = Object.values(results)[0]?.result || [];
    if (firstResult.length <= topN) {
        console.log('Отсортированный список:', firstResult);
    } else {
        console.log(`Отсортированный список (первые ${topN}):`, firstResult.slice(0, topN));
    }
}

function exportCSV(results, filename) {
    const lines = ['Алгоритм,Время (сек),Размер списка'];
    const size = Object.values(results)[0]?.result.length || 0;
    for (const [name, { elapsed }] of Object.entries(results)) {
        lines.push(`${name},${elapsed},${size}`);
    }
    fs.writeFileSync(filename, lines.join('\n'), 'utf8');
}

function exportJSON(results, filename) {
    const data = {};
    for (const [name, { result, elapsed }] of Object.entries(results)) {
        data[name] = { sorted: result, time: elapsed, size: result.length };
    }
    fs.writeFileSync(filename, JSON.stringify(data, null, 2), 'utf8');
}

function prompt(query) {
    return new Promise(resolve => rl.question(query, resolve));
}

async function interactive() {
    console.log('📊 СОРТИРОВЩИК СПИСКОВ');
    while (true) {
        console.log('\nВыберите действие:');
        console.log('1. Сортировать введённый список');
        console.log('2. Сгенерировать случайный список');
        console.log('3. Сравнить все алгоритмы');
        console.log('4. Настройки');
        console.log('0. Выход');
        const choice = await prompt('Ваш выбор: ');
        if (choice === '0') break;
        else if (choice === '1') {
            const input = await prompt('Введите числа через пробел: ');
            const data = input.split(/\s+/).map(Number).filter(n => !isNaN(n));
            if (!data.length) { console.log('Список пуст.'); continue; }
            const results = {};
            console.log('\nВыберите алгоритм (или all для всех):');
            for (const [key, { name }] of Object.entries(ALGORITHMS)) {
                console.log(`${key}. ${name}`);
            }
            const algoChoice = await prompt('Ваш выбор: ');
            if (algoChoice === 'all') {
                for (const [key, { name, func }] of Object.entries(ALGORITHMS)) {
                    const { result, elapsed } = measureTime(func, data);
                    results[name] = { result, elapsed };
                }
            } else if (ALGORITHMS[algoChoice]) {
                const { name, func } = ALGORITHMS[algoChoice];
                const { result, elapsed } = measureTime(func, data);
                results[name] = { result, elapsed };
            } else {
                console.log('Неверный выбор.');
                continue;
            }
            printTable(results);
            const exportChoice = await prompt('Экспортировать результаты? (y/n): ');
            if (exportChoice.toLowerCase() === 'y') {
                const fmt = await prompt('Формат (csv/json): ');
                const filename = await prompt('Имя файла: ') || `results.${fmt}`;
                if (fmt === 'csv') exportCSV(results, filename);
                else exportJSON(results, filename);
                console.log(`Экспортировано в ${filename}`);
            }
        } else if (choice === '2') {
            try {
                const size = parseInt(await prompt('Размер списка: '));
                const minVal = parseInt(await prompt('Минимальное значение: '));
                const maxVal = parseInt(await prompt('Максимальное значение: '));
                const data = generateRandomList(size, minVal, maxVal);
                console.log('Сгенерированный список:', data);
                const results = {};
                console.log('\nВыберите алгоритм (или all для всех):');
                for (const [key, { name }] of Object.entries(ALGORITHMS)) {
                    console.log(`${key}. ${name}`);
                }
                const algoChoice = await prompt('Ваш выбор: ');
                if (algoChoice === 'all') {
                    for (const [key, { name, func }] of Object.entries(ALGORITHMS)) {
                        const { result, elapsed } = measureTime(func, data);
                        results[name] = { result, elapsed };
                    }
                } else if (ALGORITHMS[algoChoice]) {
                    const { name, func } = ALGORITHMS[algoChoice];
                    const { result, elapsed } = measureTime(func, data);
                    results[name] = { result, elapsed };
                } else {
                    console.log('Неверный выбор.');
                    continue;
                }
                printTable(results);
            } catch (e) {
                console.log('Ошибка ввода.');
            }
        } else if (choice === '3') {
            const input = await prompt('Введите числа через пробел (или оставьте пустым для случайных): ');
            let data = input.split(/\s+/).map(Number).filter(n => !isNaN(n));
            if (!data.length) {
                const size = parseInt(await prompt('Размер случайного списка: '));
                data = generateRandomList(size);
                console.log('Сгенерированный список:', data);
            }
            const results = {};
            for (const [key, { name, func }] of Object.entries(ALGORITHMS)) {
                const { result, elapsed } = measureTime(func, data);
                results[name] = { result, elapsed };
            }
            printTable(results);
            const exportChoice = await prompt('Экспортировать результаты? (y/n): ');
            if (exportChoice.toLowerCase() === 'y') {
                const fmt = await prompt('Формат (csv/json): ');
                const filename = await prompt('Имя файла: ') || `results.${fmt}`;
                if (fmt === 'csv') exportCSV(results, filename);
                else exportJSON(results, filename);
                console.log(`Экспортировано в ${filename}`);
            }
        } else {
            console.log('Неверный выбор.');
        }
    }
    rl.close();
}

function main() {
    const args = process.argv.slice(2);
    if (args.length > 0) {
        const parsed = {};
        for (let i = 0; i < args.length; i++) {
            if (args[i] === '--list') {
                parsed.list = [];
                while (i + 1 < args.length && !args[i+1].startsWith('--')) {
                    parsed.list.push(parseInt(args[++i]));
                }
            } else if (args[i] === '--random') parsed.random = parseInt(args[++i]);
            else if (args[i] === '--algorithm') parsed.algorithm = args[++i];
            else if (args[i] === '--export-csv') parsed.exportCsv = args[++i];
            else if (args[i] === '--export-json') parsed.exportJson = args[++i];
            else if (args[i] === '--compare') parsed.compare = true;
        }
        let data = [];
        if (parsed.list && parsed.list.length) data = parsed.list;
        else if (parsed.random) data = generateRandomList(parsed.random);
        else { console.log('Укажите --list или --random'); process.exit(1); }
        const results = {};
        if (parsed.compare || parsed.algorithm === 'all') {
            for (const [key, { name, func }] of Object.entries(ALGORITHMS)) {
                const { result, elapsed } = measureTime(func, data);
                results[name] = { result, elapsed };
            }
        } else if (ALGORITHMS[parsed.algorithm]) {
            const { name, func } = ALGORITHMS[parsed.algorithm];
            const { result, elapsed } = measureTime(func, data);
            results[name] = { result, elapsed };
        } else {
            console.log('Укажите --algorithm или --compare');
            process.exit(1);
        }
        printTable(results);
        if (parsed.exportCsv) { exportCSV(results, parsed.exportCsv); console.log(`Экспортировано в ${parsed.exportCsv}`); }
        if (parsed.exportJson) { exportJSON(results, parsed.exportJson); console.log(`Экспортировано в ${parsed.exportJson}`); }
    } else {
        interactive().catch(console.error);
    }
}

if (require.main === module) {
    main();
}

// ========== Браузерная версия ==========
if (typeof window !== 'undefined') {
    window.ALGORITHMS = ALGORITHMS;
    window.measureTime = measureTime;
    window.generateRandomList = generateRandomList;
    window.printTable = printTable;
    window.exportCSV = exportCSV;
    window.exportJSON = exportJSON;
}
