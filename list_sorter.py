#!/usr/bin/env python3
"""
list_sorter.py - Сортировщик списков на Python (CLI)
Поддерживает: 6 алгоритмов, визуализацию, замер времени, экспорт.
"""
import sys
import time
import random
import json
import csv
from typing import List, Dict, Callable, Tuple

# ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
def bubble_sort(arr: List) -> List:
    """Пузырьковая сортировка"""
    n = len(arr)
    for i in range(n):
        for j in range(0, n - i - 1):
            if arr[j] > arr[j + 1]:
                arr[j], arr[j + 1] = arr[j + 1], arr[j]
    return arr

def quick_sort(arr: List) -> List:
    """Быстрая сортировка (рекурсивная)"""
    if len(arr) <= 1:
        return arr
    pivot = arr[len(arr) // 2]
    left = [x for x in arr if x < pivot]
    middle = [x for x in arr if x == pivot]
    right = [x for x in arr if x > pivot]
    return quick_sort(left) + middle + quick_sort(right)

def merge_sort(arr: List) -> List:
    """Сортировка слиянием"""
    if len(arr) <= 1:
        return arr
    mid = len(arr) // 2
    left = merge_sort(arr[:mid])
    right = merge_sort(arr[mid:])
    return merge(left, right)

def merge(left: List, right: List) -> List:
    result = []
    i, j = 0, 0
    while i < len(left) and j < len(right):
        if left[i] <= right[j]:
            result.append(left[i])
            i += 1
        else:
            result.append(right[j])
            j += 1
    result.extend(left[i:])
    result.extend(right[j:])
    return result

def selection_sort(arr: List) -> List:
    """Сортировка выбором"""
    n = len(arr)
    for i in range(n):
        min_idx = i
        for j in range(i + 1, n):
            if arr[j] < arr[min_idx]:
                min_idx = j
        arr[i], arr[min_idx] = arr[min_idx], arr[i]
    return arr

def insertion_sort(arr: List) -> List:
    """Сортировка вставками"""
    for i in range(1, len(arr)):
        key = arr[i]
        j = i - 1
        while j >= 0 and arr[j] > key:
            arr[j + 1] = arr[j]
            j -= 1
        arr[j + 1] = key
    return arr

def builtin_sort(arr: List) -> List:
    """Стандартная сортировка Python"""
    return sorted(arr)

ALGORITHMS = {
    '1': ('Пузырьковая', bubble_sort),
    '2': ('Быстрая', quick_sort),
    '3': ('Слиянием', merge_sort),
    '4': ('Стандартная', builtin_sort),
    '5': ('Выбором', selection_sort),
    '6': ('Вставками', insertion_sort),
}

# ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========
def measure_time(func: Callable, data: List) -> Tuple[List, float]:
    """Измеряет время выполнения сортировки"""
    arr = data.copy()
    start = time.perf_counter()
    result = func(arr)
    elapsed = time.perf_counter() - start
    return result, elapsed

def generate_random_list(size: int, min_val: int = 1, max_val: int = 100) -> List:
    return [random.randint(min_val, max_val) for _ in range(size)]

def print_table(results: Dict[str, Tuple[List, float]], top_n: int = 20):
    """Выводит таблицу результатов с ASCII-гистограммой"""
    if not results:
        print("Нет данных.")
        return
    max_time = max(r[1] for r in results.values())
    print("\n" + "=" * 70)
    print("РЕЗУЛЬТАТЫ СОРТИРОВКИ".center(70))
    print("=" * 70)
    for name, (sorted_data, elapsed) in results.items():
        bar_len = int((elapsed / max_time) * 40) if max_time > 0 else 0
        bar = "█" * bar_len + "░" * (40 - bar_len)
        print(f"{name:<15} {elapsed:.6f} сек.  {bar}")
    print("=" * 70)
    # Показать первые элементы
    first_result = next(iter(results.values()))[0]
    if len(first_result) <= top_n:
        print("Отсортированный список:", first_result)
    else:
        print(f"Отсортированный список (первые {top_n}):", first_result[:top_n])

def export_csv(results: Dict[str, Tuple[List, float]], filename: str):
    with open(filename, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['Алгоритм', 'Время (сек)', 'Размер списка'])
        for name, (_, elapsed) in results.items():
            writer.writerow([name, elapsed, len(next(iter(results.values()))[0])])

def export_json(results: Dict[str, Tuple[List, float]], filename: str):
    data = {
        name: {
            'sorted': sorted_data,
            'time': elapsed,
            'size': len(sorted_data)
        }
        for name, (sorted_data, elapsed) in results.items()
    }
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

def interactive():
    print("📊 СОРТИРОВЩИК СПИСКОВ")
    while True:
        print("\nВыберите действие:")
        print("1. Сортировать введённый список")
        print("2. Сгенерировать случайный список")
        print("3. Сравнить все алгоритмы")
        print("4. Настройки")
        print("0. Выход")
        choice = input("Ваш выбор: ").strip()
        if choice == '0':
            break
        elif choice == '1':
            try:
                data = list(map(int, input("Введите числа через пробел: ").split()))
                if not data:
                    print("Список пуст.")
                    continue
            except ValueError:
                print("Введите числа через пробел.")
                continue
            results = {}
            print("\nВыберите алгоритм (или 'all' для всех):")
            for key, (name, _) in ALGORITHMS.items():
                print(f"{key}. {name}")
            algo_choice = input("Ваш выбор: ").strip()
            if algo_choice == 'all':
                for key, (name, func) in ALGORITHMS.items():
                    sorted_data, elapsed = measure_time(func, data)
                    results[name] = (sorted_data, elapsed)
            elif algo_choice in ALGORITHMS:
                name, func = ALGORITHMS[algo_choice]
                sorted_data, elapsed = measure_time(func, data)
                results[name] = (sorted_data, elapsed)
            else:
                print("Неверный выбор.")
                continue
            print_table(results)
            export = input("Экспортировать результаты? (y/n): ").strip().lower()
            if export == 'y':
                fmt = input("Формат (csv/json): ").strip().lower()
                filename = input("Имя файла: ").strip()
                if not filename:
                    filename = "results." + fmt
                if fmt == 'csv':
                    export_csv(results, filename)
                else:
                    export_json(results, filename)
                print(f"Экспортировано в {filename}")
        elif choice == '2':
            try:
                size = int(input("Размер списка: "))
                min_val = int(input("Минимальное значение: "))
                max_val = int(input("Максимальное значение: "))
                data = generate_random_list(size, min_val, max_val)
                print("Сгенерированный список:", data)
                results = {}
                print("\nВыберите алгоритм (или 'all' для всех):")
                for key, (name, _) in ALGORITHMS.items():
                    print(f"{key}. {name}")
                algo_choice = input("Ваш выбор: ").strip()
                if algo_choice == 'all':
                    for key, (name, func) in ALGORITHMS.items():
                        sorted_data, elapsed = measure_time(func, data)
                        results[name] = (sorted_data, elapsed)
                elif algo_choice in ALGORITHMS:
                    name, func = ALGORITHMS[algo_choice]
                    sorted_data, elapsed = measure_time(func, data)
                    results[name] = (sorted_data, elapsed)
                else:
                    print("Неверный выбор.")
                    continue
                print_table(results)
            except ValueError:
                print("Введите корректные числа.")
        elif choice == '3':
            try:
                data = list(map(int, input("Введите числа через пробел (или оставьте пустым для случайных): ").split()))
                if not data:
                    size = int(input("Размер случайного списка: "))
                    data = generate_random_list(size)
                    print("Сгенерированный список:", data)
                results = {}
                for key, (name, func) in ALGORITHMS.items():
                    sorted_data, elapsed = measure_time(func, data)
                    results[name] = (sorted_data, elapsed)
                print_table(results)
                export = input("Экспортировать результаты? (y/n): ").strip().lower()
                if export == 'y':
                    fmt = input("Формат (csv/json): ").strip().lower()
                    filename = input("Имя файла: ").strip()
                    if not filename:
                        filename = "results." + fmt
                    if fmt == 'csv':
                        export_csv(results, filename)
                    else:
                        export_json(results, filename)
                    print(f"Экспортировано в {filename}")
            except ValueError:
                print("Введите корректные числа.")
        else:
            print("Неверный выбор.")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        # CLI с аргументами
        import argparse
        parser = argparse.ArgumentParser(description="Сортировщик списков")
        parser.add_argument("--list", nargs="+", type=int, help="Список чисел")
        parser.add_argument("--random", type=int, help="Размер случайного списка")
        parser.add_argument("--algorithm", choices=list(ALGORITHMS.keys()) + ['all'], help="Алгоритм")
        parser.add_argument("--export-csv", help="Экспорт в CSV")
        parser.add_argument("--export-json", help="Экспорт в JSON")
        parser.add_argument("--compare", action="store_true", help="Сравнить все алгоритмы")
        args = parser.parse_args()
        data = []
        if args.list:
            data = args.list
        elif args.random:
            data = generate_random_list(args.random)
        else:
            print("Укажите --list или --random")
            sys.exit(1)
        results = {}
        if args.compare or args.algorithm == 'all':
            for key, (name, func) in ALGORITHMS.items():
                sorted_data, elapsed = measure_time(func, data)
                results[name] = (sorted_data, elapsed)
        elif args.algorithm in ALGORITHMS:
            name, func = ALGORITHMS[args.algorithm]
            sorted_data, elapsed = measure_time(func, data)
            results[name] = (sorted_data, elapsed)
        else:
            print("Укажите --algorithm или --compare")
            sys.exit(1)
        print_table(results)
        if args.export_csv:
            export_csv(results, args.export_csv)
            print(f"Экспортировано в {args.export_csv}")
        if args.export_json:
            export_json(results, args.export_json)
            print(f"Экспортировано в {args.export_json}")
    else:
        interactive()
