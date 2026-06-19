// list_sorter.go - Сортировщик списков на Go (CLI)
package main

import (
	"bufio"
	"encoding/csv"
	"encoding/json"
	"flag"
	"fmt"
	"math/rand"
	"os"
	"strconv"
	"strings"
	"time"
)

// ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
func bubbleSort(arr []int) []int {
	n := len(arr)
	result := make([]int, n)
	copy(result, arr)
	for i := 0; i < n; i++ {
		for j := 0; j < n-i-1; j++ {
			if result[j] > result[j+1] {
				result[j], result[j+1] = result[j+1], result[j]
			}
		}
	}
	return result
}

func quickSort(arr []int) []int {
	if len(arr) <= 1 {
		return arr
	}
	pivot := arr[len(arr)/2]
	var left, middle, right []int
	for _, x := range arr {
		if x < pivot {
			left = append(left, x)
		} else if x == pivot {
			middle = append(middle, x)
		} else {
			right = append(right, x)
		}
	}
	left = quickSort(left)
	right = quickSort(right)
	return append(append(left, middle...), right...)
}

func mergeSort(arr []int) []int {
	if len(arr) <= 1 {
		return arr
	}
	mid := len(arr) / 2
	left := mergeSort(arr[:mid])
	right := mergeSort(arr[mid:])
	return merge(left, right)
}

func merge(left, right []int) []int {
	result := make([]int, 0, len(left)+len(right))
	i, j := 0, 0
	for i < len(left) && j < len(right) {
		if left[i] <= right[j] {
			result = append(result, left[i])
			i++
		} else {
			result = append(result, right[j])
			j++
		}
	}
	result = append(result, left[i:]...)
	result = append(result, right[j:]...)
	return result
}

func selectionSort(arr []int) []int {
	n := len(arr)
	result := make([]int, n)
	copy(result, arr)
	for i := 0; i < n; i++ {
		minIdx := i
		for j := i + 1; j < n; j++ {
			if result[j] < result[minIdx] {
				minIdx = j
			}
		}
		result[i], result[minIdx] = result[minIdx], result[i]
	}
	return result
}

func insertionSort(arr []int) []int {
	n := len(arr)
	result := make([]int, n)
	copy(result, arr)
	for i := 1; i < n; i++ {
		key := result[i]
		j := i - 1
		for j >= 0 && result[j] > key {
			result[j+1] = result[j]
			j--
		}
		result[j+1] = key
	}
	return result
}

func builtinSort(arr []int) []int {
	result := make([]int, len(arr))
	copy(result, arr)
	sortInts(result)
	return result
}

func sortInts(arr []int) {
	for i := 0; i < len(arr); i++ {
		for j := i + 1; j < len(arr); j++ {
			if arr[i] > arr[j] {
				arr[i], arr[j] = arr[j], arr[i]
			}
		}
	}
}

type Algorithm struct {
	Name string
	Func func([]int) []int
}

var algorithms = map[string]Algorithm{
	"1": {"Пузырьковая", bubbleSort},
	"2": {"Быстрая", quickSort},
	"3": {"Слиянием", mergeSort},
	"4": {"Стандартная", builtinSort},
	"5": {"Выбором", selectionSort},
	"6": {"Вставками", insertionSort},
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========
func measureTime(f func([]int) []int, data []int) ([]int, float64) {
	arr := make([]int, len(data))
	copy(arr, data)
	start := time.Now()
	result := f(arr)
	elapsed := time.Since(start).Seconds()
	return result, elapsed
}

func generateRandomList(size, minVal, maxVal int) []int {
	rand.Seed(time.Now().UnixNano())
	arr := make([]int, size)
	for i := 0; i < size; i++ {
		arr[i] = rand.Intn(maxVal-minVal+1) + minVal
	}
	return arr
}

func printTable(results map[string]struct {
	Result  []int
	Elapsed float64
}) {
	if len(results) == 0 {
		fmt.Println("Нет данных.")
		return
	}
	maxTime := 0.0
	for _, r := range results {
		if r.Elapsed > maxTime {
			maxTime = r.Elapsed
		}
	}
	fmt.Println("\n" + strings.Repeat("=", 70))
	fmt.Printf("%70s\n", "РЕЗУЛЬТАТЫ СОРТИРОВКИ")
	fmt.Println(strings.Repeat("=", 70))
	for name, r := range results {
		barLen := int((r.Elapsed / maxTime) * 40)
		if maxTime == 0 {
			barLen = 0
		}
		bar := strings.Repeat("█", barLen) + strings.Repeat("░", 40-barLen)
		fmt.Printf("%-15s %.6f сек.  %s\n", name, r.Elapsed, bar)
	}
	fmt.Println(strings.Repeat("=", 70))
	var firstResult []int
	for _, r := range results {
		firstResult = r.Result
		break
	}
	if len(firstResult) <= 20 {
		fmt.Println("Отсортированный список:", firstResult)
	} else {
		fmt.Printf("Отсортированный список (первые 20): %v\n", firstResult[:20])
	}
}

func exportCSV(results map[string]struct {
	Result  []int
	Elapsed float64
}, filename string) error {
	file, err := os.Create(filename)
	if err != nil {
		return err
	}
	defer file.Close()
	writer := csv.NewWriter(file)
	defer writer.Flush()
	writer.Write([]string{"Алгоритм", "Время (сек)", "Размер списка"})
	var size int
	for _, r := range results {
		size = len(r.Result)
		break
	}
	for name, r := range results {
		writer.Write([]string{name, fmt.Sprintf("%f", r.Elapsed), fmt.Sprintf("%d", size)})
	}
	return nil
}

func exportJSON(results map[string]struct {
	Result  []int
	Elapsed float64
}, filename string) error {
	data := make(map[string]interface{})
	for name, r := range results {
		data[name] = map[string]interface{}{
			"sorted": r.Result,
			"time":   r.Elapsed,
			"size":   len(r.Result),
		}
	}
	jsonData, err := json.MarshalIndent(data, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(filename, jsonData, 0644)
}

func interactive() {
	scanner := bufio.NewScanner(os.Stdin)
	fmt.Println("📊 СОРТИРОВЩИК СПИСКОВ")
	for {
		fmt.Println("\nВыберите действие:")
		fmt.Println("1. Сортировать введённый список")
		fmt.Println("2. Сгенерировать случайный список")
		fmt.Println("3. Сравнить все алгоритмы")
		fmt.Println("0. Выход")
		fmt.Print("Ваш выбор: ")
		scanner.Scan()
		choice := scanner.Text()
		if choice == "0" {
			break
		}
		switch choice {
		case "1":
			fmt.Print("Введите числа через пробел: ")
			scanner.Scan()
			input := scanner.Text()
			parts := strings.Fields(input)
			data := make([]int, 0, len(parts))
			for _, p := range parts {
				if n, err := strconv.Atoi(p); err == nil {
					data = append(data, n)
				}
			}
			if len(data) == 0 {
				fmt.Println("Список пуст.")
				continue
			}
			results := make(map[string]struct {
				Result  []int
				Elapsed float64
			})
			fmt.Println("\nВыберите алгоритм (или all для всех):")
			for key, alg := range algorithms {
				fmt.Printf("%s. %s\n", key, alg.Name)
			}
			fmt.Print("Ваш выбор: ")
			scanner.Scan()
			algoChoice := scanner.Text()
			if algoChoice == "all" {
				for _, alg := range algorithms {
					res, elapsed := measureTime(alg.Func, data)
					results[alg.Name] = struct {
						Result  []int
						Elapsed float64
					}{res, elapsed}
				}
			} else if alg, ok := algorithms[algoChoice]; ok {
				res, elapsed := measureTime(alg.Func, data)
				results[alg.Name] = struct {
					Result  []int
					Elapsed float64
				}{res, elapsed}
			} else {
				fmt.Println("Неверный выбор.")
				continue
			}
			printTable(results)
			fmt.Print("Экспортировать результаты? (y/n): ")
			scanner.Scan()
			if scanner.Text() == "y" {
				fmt.Print("Формат (csv/json): ")
				scanner.Scan()
				fmt.Print("Имя файла: ")
				scanner.Scan()
				filename := scanner.Text()
				if filename == "" {
					filename = "results." + scanner.Text()
				}
				var err error
				if scanner.Text() == "csv" {
					err = exportCSV(results, filename)
				} else {
					err = exportJSON(results, filename)
				}
				if err != nil {
					fmt.Printf("Ошибка: %v\n", err)
				} else {
					fmt.Printf("Экспортировано в %s\n", filename)
				}
			}
		case "2":
			fmt.Print("Размер списка: ")
			scanner.Scan()
			size, _ := strconv.Atoi(scanner.Text())
			fmt.Print("Минимальное значение: ")
			scanner.Scan()
			minVal, _ := strconv.Atoi(scanner.Text())
			fmt.Print("Максимальное значение: ")
			scanner.Scan()
			maxVal, _ := strconv.Atoi(scanner.Text())
			data := generateRandomList(size, minVal, maxVal)
			fmt.Println("Сгенерированный список:", data)
			results := make(map[string]struct {
				Result  []int
				Elapsed float64
			})
			fmt.Println("\nВыберите алгоритм (или all для всех):")
			for key, alg := range algorithms {
				fmt.Printf("%s. %s\n", key, alg.Name)
			}
			fmt.Print("Ваш выбор: ")
			scanner.Scan()
			algoChoice := scanner.Text()
			if algoChoice == "all" {
				for _, alg := range algorithms {
					res, elapsed := measureTime(alg.Func, data)
					results[alg.Name] = struct {
						Result  []int
						Elapsed float64
					}{res, elapsed}
				}
			} else if alg, ok := algorithms[algoChoice]; ok {
				res, elapsed := measureTime(alg.Func, data)
				results[alg.Name] = struct {
					Result  []int
					Elapsed float64
				}{res, elapsed}
			} else {
				fmt.Println("Неверный выбор.")
				continue
			}
			printTable(results)
		case "3":
			fmt.Print("Введите числа через пробел (или оставьте пустым для случайных): ")
			scanner.Scan()
			input := scanner.Text()
			var data []int
			if input == "" {
				fmt.Print("Размер случайного списка: ")
				scanner.Scan()
				size, _ := strconv.Atoi(scanner.Text())
				data = generateRandomList(size, 1, 100)
				fmt.Println("Сгенерированный список:", data)
			} else {
				parts := strings.Fields(input)
				for _, p := range parts {
					if n, err := strconv.Atoi(p); err == nil {
						data = append(data, n)
					}
				}
			}
			if len(data) == 0 {
				fmt.Println("Список пуст.")
				continue
			}
			results := make(map[string]struct {
				Result  []int
				Elapsed float64
			})
			for _, alg := range algorithms {
				res, elapsed := measureTime(alg.Func, data)
				results[alg.Name] = struct {
					Result  []int
					Elapsed float64
				}{res, elapsed}
			}
			printTable(results)
			fmt.Print("Экспортировать результаты? (y/n): ")
			scanner.Scan()
			if scanner.Text() == "y" {
				fmt.Print("Формат (csv/json): ")
				scanner.Scan()
				fmt.Print("Имя файла: ")
				scanner.Scan()
				filename := scanner.Text()
				if filename == "" {
					filename = "results." + scanner.Text()
				}
				var err error
				if scanner.Text() == "csv" {
					err = exportCSV(results, filename)
				} else {
					err = exportJSON(results, filename)
				}
				if err != nil {
					fmt.Printf("Ошибка: %v\n", err)
				} else {
					fmt.Printf("Экспортировано в %s\n", filename)
				}
			}
		default:
			fmt.Println("Неверный выбор.")
		}
	}
}

func main() {
	var listFlag string
	var randomSize int
	var algorithm string
	var exportCsv string
	var exportJson string
	var compare bool
	flag.StringVar(&listFlag, "list", "", "Список чисел через запятую")
	flag.IntVar(&randomSize, "random", 0, "Размер случайного списка")
	flag.StringVar(&algorithm, "algorithm", "", "Алгоритм (1-6) или all")
	flag.StringVar(&exportCsv, "export-csv", "", "Экспорт в CSV")
	flag.StringVar(&exportJson, "export-json", "", "Экспорт в JSON")
	flag.BoolVar(&compare, "compare", false, "Сравнить все алгоритмы")
	flag.Parse()

	var data []int
	if listFlag != "" {
		parts := strings.Split(listFlag, ",")
		for _, p := range parts {
			if n, err := strconv.Atoi(strings.TrimSpace(p)); err == nil {
				data = append(data, n)
			}
		}
	} else if randomSize > 0 {
		data = generateRandomList(randomSize, 1, 100)
	} else {
		interactive()
		return
	}
	if len(data) == 0 {
		fmt.Println("Список пуст.")
		return
	}
	results := make(map[string]struct {
		Result  []int
		Elapsed float64
	})
	if compare || algorithm == "all" {
		for _, alg := range algorithms {
			res, elapsed := measureTime(alg.Func, data)
			results[alg.Name] = struct {
				Result  []int
				Elapsed float64
			}{res, elapsed}
		}
	} else if alg, ok := algorithms[algorithm]; ok {
		res, elapsed := measureTime(alg.Func, data)
		results[alg.Name] = struct {
			Result  []int
			Elapsed float64
		}{res, elapsed}
	} else {
		fmt.Println("Укажите --algorithm или --compare")
		return
	}
	printTable(results)
	if exportCsv != "" {
		if err := exportCSV(results, exportCsv); err != nil {
			fmt.Printf("Ошибка: %v\n", err)
		} else {
			fmt.Printf("Экспортировано в %s\n", exportCsv)
		}
	}
	if exportJson != "" {
		if err := exportJSON(results, exportJson); err != nil {
			fmt.Printf("Ошибка: %v\n", err)
		} else {
			fmt.Printf("Экспортировано в %s\n", exportJson)
		}
	}
}
