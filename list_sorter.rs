// list_sorter.rs - Сортировщик списков на Rust (CLI)
use serde::{Serialize, Deserialize};
use std::collections::HashMap;
use std::fs;
use std::io::{self, Write, BufRead};
use std::time::Instant;
use rand::Rng;

// ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
fn bubble_sort(arr: &[i32]) -> Vec<i32> {
    let mut result = arr.to_vec();
    let n = result.len();
    for i in 0..n {
        for j in 0..n - i - 1 {
            if result[j] > result[j + 1] {
                result.swap(j, j + 1);
            }
        }
    }
    result
}

fn quick_sort(arr: &[i32]) -> Vec<i32> {
    if arr.len() <= 1 {
        return arr.to_vec();
    }
    let pivot = arr[arr.len() / 2];
    let left: Vec<i32> = arr.iter().filter(|&&x| x < pivot).cloned().collect();
    let middle: Vec<i32> = arr.iter().filter(|&&x| x == pivot).cloned().collect();
    let right: Vec<i32> = arr.iter().filter(|&&x| x > pivot).cloned().collect();
    let mut result = quick_sort(&left);
    result.extend(middle);
    result.extend(quick_sort(&right));
    result
}

fn merge_sort(arr: &[i32]) -> Vec<i32> {
    if arr.len() <= 1 {
        return arr.to_vec();
    }
    let mid = arr.len() / 2;
    let left = merge_sort(&arr[..mid]);
    let right = merge_sort(&arr[mid..]);
    merge(&left, &right)
}

fn merge(left: &[i32], right: &[i32]) -> Vec<i32> {
    let mut result = Vec::with_capacity(left.len() + right.len());
    let mut i = 0;
    let mut j = 0;
    while i < left.len() && j < right.len() {
        if left[i] <= right[j] {
            result.push(left[i]);
            i += 1;
        } else {
            result.push(right[j]);
            j += 1;
        }
    }
    result.extend_from_slice(&left[i..]);
    result.extend_from_slice(&right[j..]);
    result
}

fn selection_sort(arr: &[i32]) -> Vec<i32> {
    let mut result = arr.to_vec();
    let n = result.len();
    for i in 0..n {
        let mut min_idx = i;
        for j in i + 1..n {
            if result[j] < result[min_idx] {
                min_idx = j;
            }
        }
        result.swap(i, min_idx);
    }
    result
}

fn insertion_sort(arr: &[i32]) -> Vec<i32> {
    let mut result = arr.to_vec();
    for i in 1..result.len() {
        let key = result[i];
        let mut j = i as isize - 1;
        while j >= 0 && result[j as usize] > key {
            result[(j + 1) as usize] = result[j as usize];
            j -= 1;
        }
        result[(j + 1) as usize] = key;
    }
    result
}

fn builtin_sort(arr: &[i32]) -> Vec<i32> {
    let mut result = arr.to_vec();
    result.sort();
    result
}

type SortFunc = fn(&[i32]) -> Vec<i32>;

#[derive(Clone)]
struct Algorithm {
    name: String,
    func: SortFunc,
}

lazy_static::lazy_static! {
    static ref ALGORITHMS: HashMap<String, Algorithm> = {
        let mut m = HashMap::new();
        m.insert("1".to_string(), Algorithm { name: "Пузырьковая".to_string(), func: bubble_sort });
        m.insert("2".to_string(), Algorithm { name: "Быстрая".to_string(), func: quick_sort });
        m.insert("3".to_string(), Algorithm { name: "Слиянием".to_string(), func: merge_sort });
        m.insert("4".to_string(), Algorithm { name: "Стандартная".to_string(), func: builtin_sort });
        m.insert("5".to_string(), Algorithm { name: "Выбором".to_string(), func: selection_sort });
        m.insert("6".to_string(), Algorithm { name: "Вставками".to_string(), func: insertion_sort });
        m
    };
}

#[derive(Serialize, Deserialize)]
struct SortResult {
    sorted: Vec<i32>,
    time: f64,
    size: usize,
}

fn measure_time(func: SortFunc, data: &[i32]) -> (Vec<i32>, f64) {
    let start = Instant::now();
    let result = func(data);
    let elapsed = start.elapsed().as_secs_f64();
    (result, elapsed)
}

fn generate_random_list(size: usize, min_val: i32, max_val: i32) -> Vec<i32> {
    let mut rng = rand::thread_rng();
    (0..size).map(|_| rng.gen_range(min_val..=max_val)).collect()
}

fn print_table(results: &HashMap<String, (Vec<i32>, f64)>) {
    if results.is_empty() {
        println!("Нет данных.");
        return;
    }
    let max_time = results.values().map(|(_, t)| *t).fold(0.0, f64::max);
    println!("\n{}", "=".repeat(70));
    println!("{:^70}", "РЕЗУЛЬТАТЫ СОРТИРОВКИ");
    println!("{}", "=".repeat(70));
    for (name, (_, elapsed)) in results {
        let bar_len = if max_time > 0.0 { (elapsed / max_time * 40.0) as usize } else { 0 };
        let bar = "█".repeat(bar_len) + &"░".repeat(40 - bar_len);
        println!("{:<15} {:.6} сек.  {}", name, elapsed, bar);
    }
    println!("{}", "=".repeat(70));
    if let Some((_, (sorted, _))) = results.iter().next() {
        if sorted.len() <= 20 {
            println!("Отсортированный список: {:?}", sorted);
        } else {
            println!("Отсортированный список (первые 20): {:?}", &sorted[..20]);
        }
    }
}

fn export_csv(results: &HashMap<String, (Vec<i32>, f64)>, filename: &str) -> Result<(), Box<dyn std::error::Error>> {
    let mut writer = csv::Writer::from_path(filename)?;
    writer.write_record(&["Алгоритм", "Время (сек)", "Размер списка"])?;
    let size = results.values().next().map(|(v, _)| v.len()).unwrap_or(0);
    for (name, (_, elapsed)) in results {
        writer.serialize((name, elapsed, size))?;
    }
    writer.flush()?;
    Ok(())
}

fn export_json(results: &HashMap<String, (Vec<i32>, f64)>, filename: &str) -> Result<(), Box<dyn std::error::Error>> {
    let mut data = HashMap::new();
    for (name, (sorted, time)) in results {
        data.insert(name.clone(), SortResult {
            sorted: sorted.clone(),
            time: *time,
            size: sorted.len(),
        });
    }
    let json = serde_json::to_string_pretty(&data)?;
    fs::write(filename, json)?;
    Ok(())
}

fn read_line(prompt: &str) -> String {
    print!("{}", prompt);
    io::stdout().flush().unwrap();
    let mut input = String::new();
    io::stdin().read_line(&mut input).unwrap();
    input.trim().to_string()
}

fn parse_numbers(s: &str) -> Vec<i32> {
    s.split_whitespace()
        .filter_map(|x| x.parse::<i32>().ok())
        .collect()
}

fn interactive() {
    println!("📊 СОРТИРОВЩИК СПИСКОВ");
    loop {
        println!("\nВыберите действие:");
        println!("1. Сортировать введённый список");
        println!("2. Сгенерировать случайный список");
        println!("3. Сравнить все алгоритмы");
        println!("0. Выход");
        let choice = read_line("Ваш выбор: ");
        match choice.as_str() {
            "0" => break,
            "1" => {
                let input = read_line("Введите числа через пробел: ");
                let data = parse_numbers(&input);
                if data.is_empty() {
                    println!("Список пуст.");
                    continue;
                }
                let mut results = HashMap::new();
                println!("\nВыберите алгоритм (или all для всех):");
                for (key, alg) in ALGORITHMS.iter() {
                    println!("{}. {}", key, alg.name);
                }
                let algo_choice = read_line("Ваш выбор: ");
                if algo_choice == "all" {
                    for (_, alg) in ALGORITHMS.iter() {
                        let (sorted, elapsed) = measure_time(alg.func, &data);
                        results.insert(alg.name.clone(), (sorted, elapsed));
                    }
                } else if let Some(alg) = ALGORITHMS.get(&algo_choice) {
                    let (sorted, elapsed) = measure_time(alg.func, &data);
                    results.insert(alg.name.clone(), (sorted, elapsed));
                } else {
                    println!("Неверный выбор.");
                    continue;
                }
                print_table(&results);
                let export = read_line("Экспортировать результаты? (y/n): ");
                if export.to_lowercase() == "y" {
                    let fmt = read_line("Формат (csv/json): ");
                    let filename = read_line("Имя файла: ");
                    let filename = if filename.is_empty() { format!("results.{}", fmt) } else { filename };
                    let res = if fmt == "csv" {
                        export_csv(&results, &filename)
                    } else {
                        export_json(&results, &filename)
                    };
                    if let Err(e) = res {
                        println!("Ошибка: {}", e);
                    } else {
                        println!("Экспортировано в {}", filename);
                    }
                }
            }
            "2" => {
                let size = read_line("Размер списка: ").parse::<usize>().unwrap_or(10);
                let min_val = read_line("Минимальное значение: ").parse::<i32>().unwrap_or(1);
                let max_val = read_line("Максимальное значение: ").parse::<i32>().unwrap_or(100);
                let data = generate_random_list(size, min_val, max_val);
                println!("Сгенерированный список: {:?}", data);
                let mut results = HashMap::new();
                println!("\nВыберите алгоритм (или all для всех):");
                for (key, alg) in ALGORITHMS.iter() {
                    println!("{}. {}", key, alg.name);
                }
                let algo_choice = read_line("Ваш выбор: ");
                if algo_choice == "all" {
                    for (_, alg) in ALGORITHMS.iter() {
                        let (sorted, elapsed) = measure_time(alg.func, &data);
                        results.insert(alg.name.clone(), (sorted, elapsed));
                    }
                } else if let Some(alg) = ALGORITHMS.get(&algo_choice) {
                    let (sorted, elapsed) = measure_time(alg.func, &data);
                    results.insert(alg.name.clone(), (sorted, elapsed));
                } else {
                    println!("Неверный выбор.");
                    continue;
                }
                print_table(&results);
            }
            "3" => {
                let input = read_line("Введите числа через пробел (или оставьте пустым для случайных): ");
                let data = if input.is_empty() {
                    let size = read_line("Размер случайного списка: ").parse::<usize>().unwrap_or(10);
                    let list = generate_random_list(size, 1, 100);
                    println!("Сгенерированный список: {:?}", list);
                    list
                } else {
                    parse_numbers(&input)
                };
                if data.is_empty() {
                    println!("Список пуст.");
                    continue;
                }
                let mut results = HashMap::new();
                for (_, alg) in ALGORITHMS.iter() {
                    let (sorted, elapsed) = measure_time(alg.func, &data);
                    results.insert(alg.name.clone(), (sorted, elapsed));
                }
                print_table(&results);
                let export = read_line("Экспортировать результаты? (y/n): ");
                if export.to_lowercase() == "y" {
                    let fmt = read_line("Формат (csv/json): ");
                    let filename = read_line("Имя файла: ");
                    let filename = if filename.is_empty() { format!("results.{}", fmt) } else { filename };
                    let res = if fmt == "csv" {
                        export_csv(&results, &filename)
                    } else {
                        export_json(&results, &filename)
                    };
                    if let Err(e) = res {
                        println!("Ошибка: {}", e);
                    } else {
                        println!("Экспортировано в {}", filename);
                    }
                }
            }
            _ => println!("Неверный выбор."),
        }
    }
}

fn main() {
    let args: Vec<String> = std::env::args().collect();
    if args.len() > 1 {
        let mut list = Vec::new();
        let mut random_size = 0;
        let mut algorithm = String::new();
        let mut export_csv = String::new();
        let mut export_json = String::new();
        let mut compare = false;
        let mut i = 1;
        while i < args.len() {
            match args[i].as_str() {
                "--list" => {
                    while i + 1 < args.len() && !args[i+1].starts_with("--") {
                        if let Ok(n) = args[i+1].parse::<i32>() {
                            list.push(n);
                        }
                        i += 1;
                    }
                }
                "--random" => {
                    random_size = args[i+1].parse().unwrap_or(0);
                    i += 1;
                }
                "--algorithm" => {
                    algorithm = args[i+1].clone();
                    i += 1;
                }
                "--export-csv" => {
                    export_csv = args[i+1].clone();
                    i += 1;
                }
                "--export-json" => {
                    export_json = args[i+1].clone();
                    i += 1;
                }
                "--compare" => {
                    compare = true;
                }
                _ => {}
            }
            i += 1;
        }
        let data = if !list.is_empty() {
            list
        } else if random_size > 0 {
            generate_random_list(random_size, 1, 100)
        } else {
            println!("Укажите --list или --random");
            return;
        };
        let mut results = HashMap::new();
        if compare || algorithm == "all" {
            for (_, alg) in ALGORITHMS.iter() {
                let (sorted, elapsed) = measure_time(alg.func, &data);
                results.insert(alg.name.clone(), (sorted, elapsed));
            }
        } else if let Some(alg) = ALGORITHMS.get(&algorithm) {
            let (sorted, elapsed) = measure_time(alg.func, &data);
            results.insert(alg.name.clone(), (sorted, elapsed));
        } else {
            println!("Укажите --algorithm или --compare");
            return;
        }
        print_table(&results);
        if !export_csv.is_empty() {
            if let Err(e) = export_csv(&results, &export_csv) {
                println!("Ошибка: {}", e);
            } else {
                println!("Экспортировано в {}", export_csv);
            }
        }
        if !export_json.is_empty() {
            if let Err(e) = export_json(&results, &export_json) {
                println!("Ошибка: {}", e);
            } else {
                println!("Экспортировано в {}", export_json);
            }
        }
    } else {
        interactive();
    }
}
