// ListSorter.cs - Сортировщик списков на C# (CLI)
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using System.Diagnostics;

namespace ListSorter
{
    class Program
    {
        private static readonly Random random = new Random();

        // ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
        static List<int> BubbleSort(List<int> arr)
        {
            var result = new List<int>(arr);
            int n = result.Count;
            for (int i = 0; i < n; i++)
                for (int j = 0; j < n - i - 1; j++)
                    if (result[j] > result[j + 1])
                        (result[j], result[j + 1]) = (result[j + 1], result[j]);
            return result;
        }

        static List<int> QuickSort(List<int> arr)
        {
            if (arr.Count <= 1) return new List<int>(arr);
            int pivot = arr[arr.Count / 2];
            var left = arr.Where(x => x < pivot).ToList();
            var middle = arr.Where(x => x == pivot).ToList();
            var right = arr.Where(x => x > pivot).ToList();
            var result = QuickSort(left);
            result.AddRange(middle);
            result.AddRange(QuickSort(right));
            return result;
        }

        static List<int> MergeSort(List<int> arr)
        {
            if (arr.Count <= 1) return new List<int>(arr);
            int mid = arr.Count / 2;
            var left = MergeSort(arr.Take(mid).ToList());
            var right = MergeSort(arr.Skip(mid).ToList());
            return Merge(left, right);
        }

        static List<int> Merge(List<int> left, List<int> right)
        {
            var result = new List<int>();
            int i = 0, j = 0;
            while (i < left.Count && j < right.Count)
            {
                if (left[i] <= right[j]) result.Add(left[i++]);
                else result.Add(right[j++]);
            }
            result.AddRange(left.Skip(i));
            result.AddRange(right.Skip(j));
            return result;
        }

        static List<int> SelectionSort(List<int> arr)
        {
            var result = new List<int>(arr);
            int n = result.Count;
            for (int i = 0; i < n; i++)
            {
                int minIdx = i;
                for (int j = i + 1; j < n; j++)
                    if (result[j] < result[minIdx]) minIdx = j;
                (result[i], result[minIdx]) = (result[minIdx], result[i]);
            }
            return result;
        }

        static List<int> InsertionSort(List<int> arr)
        {
            var result = new List<int>(arr);
            for (int i = 1; i < result.Count; i++)
            {
                int key = result[i];
                int j = i - 1;
                while (j >= 0 && result[j] > key)
                {
                    result[j + 1] = result[j];
                    j--;
                }
                result[j + 1] = key;
            }
            return result;
        }

        static List<int> BuiltinSort(List<int> arr)
        {
            var result = new List<int>(arr);
            result.Sort();
            return result;
        }

        static Dictionary<string, Func<List<int>, List<int>>> algorithms = new Dictionary<string, Func<List<int>, List<int>>>
        {
            {"1", BubbleSort},
            {"2", QuickSort},
            {"3", MergeSort},
            {"4", BuiltinSort},
            {"5", SelectionSort},
            {"6", InsertionSort}
        };

        static Dictionary<string, string> algoNames = new Dictionary<string, string>
        {
            {"1", "Пузырьковая"},
            {"2", "Быстрая"},
            {"3", "Слиянием"},
            {"4", "Стандартная"},
            {"5", "Выбором"},
            {"6", "Вставками"}
        };

        // ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========
        static List<int> GenerateRandomList(int size, int minVal, int maxVal)
        {
            var list = new List<int>();
            for (int i = 0; i < size; i++)
                list.Add(random.Next(minVal, maxVal + 1));
            return list;
        }

        static (List<int> result, double elapsed) MeasureTime(Func<List<int>, List<int>> func, List<int> data)
        {
            var arr = new List<int>(data);
            var sw = Stopwatch.StartNew();
            var result = func(arr);
            sw.Stop();
            return (result, sw.Elapsed.TotalSeconds);
        }

        static void PrintTable(Dictionary<string, (List<int> result, double elapsed)> results, int topN = 20)
        {
            if (results.Count == 0) { Console.WriteLine("Нет данных."); return; }
            double maxTime = results.Values.Max(r => r.elapsed);
            Console.WriteLine("\n" + new string('=', 70));
            Console.WriteLine("РЕЗУЛЬТАТЫ СОРТИРОВКИ".PadLeft(35).PadRight(70));
            Console.WriteLine(new string('=', 70));
            foreach (var kv in results)
            {
                string name = kv.Key;
                double elapsed = kv.Value.elapsed;
                int barLen = maxTime > 0 ? (int)((elapsed / maxTime) * 40) : 0;
                string bar = new string('█', barLen) + new string('░', 40 - barLen);
                Console.WriteLine($"{name,-15} {elapsed,6:F6} сек.  {bar}");
            }
            Console.WriteLine(new string('=', 70));
            var firstResult = results.Values.First().result;
            if (firstResult.Count <= topN)
                Console.WriteLine("Отсортированный список: " + string.Join(", ", firstResult));
            else
                Console.WriteLine($"Отсортированный список (первые {topN}): " + string.Join(", ", firstResult.Take(topN)));
        }

        static void ExportCSV(Dictionary<string, (List<int> result, double elapsed)> results, string filename)
        {
            using (var sw = new StreamWriter(filename))
            {
                sw.WriteLine("Алгоритм,Время (сек),Размер списка");
                int size = results.Values.First().result.Count;
                foreach (var kv in results)
                    sw.WriteLine($"{kv.Key},{kv.Value.elapsed},{size}");
            }
        }

        static void ExportJSON(Dictionary<string, (List<int> result, double elapsed)> results, string filename)
        {
            var data = new Dictionary<string, object>();
            int size = results.Values.First().result.Count;
            foreach (var kv in results)
            {
                data[kv.Key] = new { sorted = kv.Value.result, time = kv.Value.elapsed, size = size };
            }
            string json = JsonSerializer.Serialize(data, new JsonSerializerOptions { WriteIndented = true });
            File.WriteAllText(filename, json);
        }

        static async Task Interactive()
        {
            Console.WriteLine("📊 СОРТИРОВЩИК СПИСКОВ");
            while (true)
            {
                Console.WriteLine("\nВыберите действие:");
                Console.WriteLine("1. Сортировать введённый список");
                Console.WriteLine("2. Сгенерировать случайный список");
                Console.WriteLine("3. Сравнить все алгоритмы");
                Console.WriteLine("0. Выход");
                Console.Write("Ваш выбор: ");
                string choice = Console.ReadLine();
                if (choice == "0") break;
                else if (choice == "1")
                {
                    Console.Write("Введите числа через пробел: ");
                    string input = Console.ReadLine();
                    var data = input.Split(' ', StringSplitOptions.RemoveEmptyEntries)
                                    .Select(s => int.TryParse(s, out int n) ? n : (int?)null)
                                    .Where(n => n.HasValue).Select(n => n.Value).ToList();
                    if (data.Count == 0) { Console.WriteLine("Список пуст."); continue; }
                    var results = new Dictionary<string, (List<int>, double)>();
                    Console.WriteLine("\nВыберите алгоритм (или all для всех):");
                    foreach (var kv in algorithms)
                        Console.WriteLine($"{kv.Key}. {algoNames[kv.Key]}");
                    Console.Write("Ваш выбор: ");
                    string algoChoice = Console.ReadLine();
                    if (algoChoice == "all")
                    {
                        foreach (var kv in algorithms)
                        {
                            var (result, elapsed) = MeasureTime(kv.Value, data);
                            results[algoNames[kv.Key]] = (result, elapsed);
                        }
                    }
                    else if (algorithms.ContainsKey(algoChoice))
                    {
                        var (result, elapsed) = MeasureTime(algorithms[algoChoice], data);
                        results[algoNames[algoChoice]] = (result, elapsed);
                    }
                    else { Console.WriteLine("Неверный выбор."); continue; }
                    PrintTable(results);
                    Console.Write("Экспортировать результаты? (y/n): ");
                    if (Console.ReadLine()?.ToLower() == "y")
                    {
                        Console.Write("Формат (csv/json): ");
                        string fmt = Console.ReadLine();
                        Console.Write("Имя файла: ");
                        string filename = Console.ReadLine();
                        if (string.IsNullOrEmpty(filename)) filename = $"results.{fmt}";
                        if (fmt == "csv") ExportCSV(results, filename);
                        else ExportJSON(results, filename);
                        Console.WriteLine($"Экспортировано в {filename}");
                    }
                }
                else if (choice == "2")
                {
                    Console.Write("Размер списка: ");
                    int size = int.Parse(Console.ReadLine());
                    Console.Write("Минимальное значение: ");
                    int minVal = int.Parse(Console.ReadLine());
                    Console.Write("Максимальное значение: ");
                    int maxVal = int.Parse(Console.ReadLine());
                    var data = GenerateRandomList(size, minVal, maxVal);
                    Console.WriteLine("Сгенерированный список: " + string.Join(", ", data));
                    var results = new Dictionary<string, (List<int>, double)>();
                    Console.WriteLine("\nВыберите алгоритм (или all для всех):");
                    foreach (var kv in algorithms)
                        Console.WriteLine($"{kv.Key}. {algoNames[kv.Key]}");
                    Console.Write("Ваш выбор: ");
                    string algoChoice = Console.ReadLine();
                    if (algoChoice == "all")
                    {
                        foreach (var kv in algorithms)
                        {
                            var (result, elapsed) = MeasureTime(kv.Value, data);
                            results[algoNames[kv.Key]] = (result, elapsed);
                        }
                    }
                    else if (algorithms.ContainsKey(algoChoice))
                    {
                        var (result, elapsed) = MeasureTime(algorithms[algoChoice], data);
                        results[algoNames[algoChoice]] = (result, elapsed);
                    }
                    else { Console.WriteLine("Неверный выбор."); continue; }
                    PrintTable(results);
                }
                else if (choice == "3")
                {
                    Console.Write("Введите числа через пробел (или оставьте пустым для случайных): ");
                    string input = Console.ReadLine();
                    List<int> data;
                    if (string.IsNullOrWhiteSpace(input))
                    {
                        Console.Write("Размер случайного списка: ");
                        int size = int.Parse(Console.ReadLine());
                        data = GenerateRandomList(size, 1, 100);
                        Console.WriteLine("Сгенерированный список: " + string.Join(", ", data));
                    }
                    else
                    {
                        data = input.Split(' ', StringSplitOptions.RemoveEmptyEntries)
                                     .Select(s => int.TryParse(s, out int n) ? n : (int?)null)
                                     .Where(n => n.HasValue).Select(n => n.Value).ToList();
                    }
                    if (data.Count == 0) { Console.WriteLine("Список пуст."); continue; }
                    var results = new Dictionary<string, (List<int>, double)>();
                    foreach (var kv in algorithms)
                    {
                        var (result, elapsed) = MeasureTime(kv.Value, data);
                        results[algoNames[kv.Key]] = (result, elapsed);
                    }
                    PrintTable(results);
                    Console.Write("Экспортировать результаты? (y/n): ");
                    if (Console.ReadLine()?.ToLower() == "y")
                    {
                        Console.Write("Формат (csv/json): ");
                        string fmt = Console.ReadLine();
                        Console.Write("Имя файла: ");
                        string filename = Console.ReadLine();
                        if (string.IsNullOrEmpty(filename)) filename = $"results.{fmt}";
                        if (fmt == "csv") ExportCSV(results, filename);
                        else ExportJSON(results, filename);
                        Console.WriteLine($"Экспортировано в {filename}");
                    }
                }
                else
                {
                    Console.WriteLine("Неверный выбор.");
                }
            }
        }

        static async Task Main(string[] args)
        {
            if (args.Length > 0)
            {
                string listFlag = null, algorithm = null, exportCsv = null, exportJson = null;
                bool compare = false;
                int randomSize = 0;
                for (int i = 0; i < args.Length; i++)
                {
                    if (args[i] == "--list") listFlag = args[++i];
                    else if (args[i] == "--random") randomSize = int.Parse(args[++i]);
                    else if (args[i] == "--algorithm") algorithm = args[++i];
                    else if (args[i] == "--export-csv") exportCsv = args[++i];
                    else if (args[i] == "--export-json") exportJson = args[++i];
                    else if (args[i] == "--compare") compare = true;
                }
                List<int> data = new List<int>();
                if (listFlag != null)
                {
                    foreach (var p in listFlag.Split(','))
                        if (int.TryParse(p.Trim(), out int n)) data.Add(n);
                }
                else if (randomSize > 0)
                {
                    data = GenerateRandomList(randomSize, 1, 100);
                }
                else
                {
                    Console.WriteLine("Укажите --list или --random");
                    return;
                }
                if (data.Count == 0) { Console.WriteLine("Список пуст."); return; }
                var results = new Dictionary<string, (List<int>, double)>();
                if (compare || algorithm == "all")
                {
                    foreach (var kv in algorithms)
                    {
                        var (result, elapsed) = MeasureTime(kv.Value, data);
                        results[algoNames[kv.Key]] = (result, elapsed);
                    }
                }
                else if (algorithms.ContainsKey(algorithm))
                {
                    var (result, elapsed) = MeasureTime(algorithms[algorithm], data);
                    results[algoNames[algorithm]] = (result, elapsed);
                }
                else
                {
                    Console.WriteLine("Укажите --algorithm или --compare");
                    return;
                }
                PrintTable(results);
                if (exportCsv != null) { ExportCSV(results, exportCsv); Console.WriteLine($"Экспортировано в {exportCsv}"); }
                if (exportJson != null) { ExportJSON(results, exportJson); Console.WriteLine($"Экспортировано в {exportJson}"); }
            }
            else
            {
                await Interactive();
            }
        }
    }
}
