// ListSorter.java - Сортировщик списков на Java (CLI)
import java.io.*;
import java.nio.file.*;
import java.util.*;
import java.time.*;

public class ListSorter {
    private static final Scanner scanner = new Scanner(System.in);
    private static final Random random = new Random();

    interface SortFunc {
        List<Integer> sort(List<Integer> arr);
    }

    // ========== АЛГОРИТМЫ СОРТИРОВКИ ==========
    static class Algorithms {
        static List<Integer> bubbleSort(List<Integer> arr) {
            List<Integer> result = new ArrayList<>(arr);
            int n = result.size();
            for (int i = 0; i < n; i++) {
                for (int j = 0; j < n - i - 1; j++) {
                    if (result.get(j) > result.get(j + 1)) {
                        Collections.swap(result, j, j + 1);
                    }
                }
            }
            return result;
        }

        static List<Integer> quickSort(List<Integer> arr) {
            if (arr.size() <= 1) return new ArrayList<>(arr);
            int pivot = arr.get(arr.size() / 2);
            List<Integer> left = new ArrayList<>();
            List<Integer> middle = new ArrayList<>();
            List<Integer> right = new ArrayList<>();
            for (int x : arr) {
                if (x < pivot) left.add(x);
                else if (x == pivot) middle.add(x);
                else right.add(x);
            }
            List<Integer> result = quickSort(left);
            result.addAll(middle);
            result.addAll(quickSort(right));
            return result;
        }

        static List<Integer> mergeSort(List<Integer> arr) {
            if (arr.size() <= 1) return new ArrayList<>(arr);
            int mid = arr.size() / 2;
            List<Integer> left = mergeSort(arr.subList(0, mid));
            List<Integer> right = mergeSort(arr.subList(mid, arr.size()));
            return merge(left, right);
        }

        static List<Integer> merge(List<Integer> left, List<Integer> right) {
            List<Integer> result = new ArrayList<>();
            int i = 0, j = 0;
            while (i < left.size() && j < right.size()) {
                if (left.get(i) <= right.get(j)) {
                    result.add(left.get(i++));
                } else {
                    result.add(right.get(j++));
                }
            }
            result.addAll(left.subList(i, left.size()));
            result.addAll(right.subList(j, right.size()));
            return result;
        }

        static List<Integer> selectionSort(List<Integer> arr) {
            List<Integer> result = new ArrayList<>(arr);
            int n = result.size();
            for (int i = 0; i < n; i++) {
                int minIdx = i;
                for (int j = i + 1; j < n; j++) {
                    if (result.get(j) < result.get(minIdx)) minIdx = j;
                }
                Collections.swap(result, i, minIdx);
            }
            return result;
        }

        static List<Integer> insertionSort(List<Integer> arr) {
            List<Integer> result = new ArrayList<>(arr);
            for (int i = 1; i < result.size(); i++) {
                int key = result.get(i);
                int j = i - 1;
                while (j >= 0 && result.get(j) > key) {
                    result.set(j + 1, result.get(j));
                    j--;
                }
                result.set(j + 1, key);
            }
            return result;
        }

        static List<Integer> builtinSort(List<Integer> arr) {
            List<Integer> result = new ArrayList<>(arr);
            Collections.sort(result);
            return result;
        }
    }

    static class Algorithm {
        String name;
        SortFunc func;
        Algorithm(String name, SortFunc func) {
            this.name = name;
            this.func = func;
        }
    }

    private static final Map<String, Algorithm> ALGORITHMS = new LinkedHashMap<>();
    static {
        ALGORITHMS.put("1", new Algorithm("Пузырьковая", Algorithms::bubbleSort));
        ALGORITHMS.put("2", new Algorithm("Быстрая", Algorithms::quickSort));
        ALGORITHMS.put("3", new Algorithm("Слиянием", Algorithms::mergeSort));
        ALGORITHMS.put("4", new Algorithm("Стандартная", Algorithms::builtinSort));
        ALGORITHMS.put("5", new Algorithm("Выбором", Algorithms::selectionSort));
        ALGORITHMS.put("6", new Algorithm("Вставками", Algorithms::insertionSort));
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========
    static List<Integer> generateRandomList(int size, int minVal, int maxVal) {
        List<Integer> list = new ArrayList<>();
        for (int i = 0; i < size; i++) {
            list.add(random.nextInt(maxVal - minVal + 1) + minVal);
        }
        return list;
    }

    static Map.Entry<List<Integer>, Double> measureTime(SortFunc func, List<Integer> data) {
        List<Integer> arr = new ArrayList<>(data);
        Instant start = Instant.now();
        List<Integer> result = func.sort(arr);
        double elapsed = Duration.between(start, Instant.now()).toNanos() / 1_000_000_000.0;
        return Map.entry(result, elapsed);
    }

    static void printTable(Map<String, Map.Entry<List<Integer>, Double>> results) {
        if (results.isEmpty()) {
            System.out.println("Нет данных.");
            return;
        }
        double maxTime = results.values().stream().mapToDouble(Map.Entry::getValue).max().orElse(0);
        System.out.println("\n" + "=".repeat(70));
        System.out.printf("%70s\n", "РЕЗУЛЬТАТЫ СОРТИРОВКИ");
        System.out.println("=".repeat(70));
        for (Map.Entry<String, Map.Entry<List<Integer>, Double>> entry : results.entrySet()) {
            String name = entry.getKey();
            double elapsed = entry.getValue().getValue();
            int barLen = maxTime > 0 ? (int)((elapsed / maxTime) * 40) : 0;
            String bar = "█".repeat(barLen) + "░".repeat(40 - barLen);
            System.out.printf("%-15s %.6f сек.  %s\n", name, elapsed, bar);
        }
        System.out.println("=".repeat(70));
        if (!results.isEmpty()) {
            List<Integer> firstResult = results.values().iterator().next().getKey();
            if (firstResult.size() <= 20) {
                System.out.println("Отсортированный список: " + firstResult);
            } else {
                System.out.println("Отсортированный список (первые 20): " + firstResult.subList(0, 20));
            }
        }
    }

    static void exportCSV(Map<String, Map.Entry<List<Integer>, Double>> results, String filename) throws IOException {
        try (PrintWriter pw = new PrintWriter(filename)) {
            pw.println("Алгоритм,Время (сек),Размер списка");
            int size = results.values().iterator().next().getKey().size();
            for (Map.Entry<String, Map.Entry<List<Integer>, Double>> entry : results.entrySet()) {
                pw.printf("%s,%f,%d\n", entry.getKey(), entry.getValue().getValue(), size);
            }
        }
    }

    static void exportJSON(Map<String, Map.Entry<List<Integer>, Double>> results, String filename) throws IOException {
        StringBuilder sb = new StringBuilder();
        sb.append("{\n");
        boolean first = true;
        int size = results.values().iterator().next().getKey().size();
        for (Map.Entry<String, Map.Entry<List<Integer>, Double>> entry : results.entrySet()) {
            if (!first) sb.append(",\n");
            first = false;
            sb.append("  \"").append(entry.getKey()).append("\": {\n");
            sb.append("    \"sorted\": ").append(entry.getValue().getKey()).append(",\n");
            sb.append("    \"time\": ").append(entry.getValue().getValue()).append(",\n");
            sb.append("    \"size\": ").append(size).append("\n");
            sb.append("  }");
        }
        sb.append("\n}");
        Files.write(Paths.get(filename), sb.toString().getBytes());
    }

    static void interactive() throws IOException {
        System.out.println("📊 СОРТИРОВЩИК СПИСКОВ");
        while (true) {
            System.out.println("\nВыберите действие:");
            System.out.println("1. Сортировать введённый список");
            System.out.println("2. Сгенерировать случайный список");
            System.out.println("3. Сравнить все алгоритмы");
            System.out.println("0. Выход");
            System.out.print("Ваш выбор: ");
            String choice = scanner.nextLine().trim();
            if (choice.equals("0")) break;
            else if (choice.equals("1")) {
                System.out.print("Введите числа через пробел: ");
                String[] parts = scanner.nextLine().trim().split("\\s+");
                List<Integer> data = new ArrayList<>();
                for (String p : parts) {
                    try { data.add(Integer.parseInt(p)); } catch (NumberFormatException e) {}
                }
                if (data.isEmpty()) { System.out.println("Список пуст."); continue; }
                Map<String, Map.Entry<List<Integer>, Double>> results = new LinkedHashMap<>();
                System.out.println("\nВыберите алгоритм (или all для всех):");
                for (Map.Entry<String, Algorithm> entry : ALGORITHMS.entrySet()) {
                    System.out.println(entry.getKey() + ". " + entry.getValue().name);
                }
                System.out.print("Ваш выбор: ");
                String algoChoice = scanner.nextLine().trim();
                if (algoChoice.equals("all")) {
                    for (Algorithm alg : ALGORITHMS.values()) {
                        Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                        results.put(alg.name, res);
                    }
                } else if (ALGORITHMS.containsKey(algoChoice)) {
                    Algorithm alg = ALGORITHMS.get(algoChoice);
                    Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                    results.put(alg.name, res);
                } else {
                    System.out.println("Неверный выбор.");
                    continue;
                }
                printTable(results);
                System.out.print("Экспортировать результаты? (y/n): ");
                String export = scanner.nextLine().trim().toLowerCase();
                if (export.equals("y")) {
                    System.out.print("Формат (csv/json): ");
                    String fmt = scanner.nextLine().trim();
                    System.out.print("Имя файла: ");
                    String filename = scanner.nextLine().trim();
                    if (filename.isEmpty()) filename = "results." + fmt;
                    if (fmt.equals("csv")) exportCSV(results, filename);
                    else exportJSON(results, filename);
                    System.out.println("Экспортировано в " + filename);
                }
            } else if (choice.equals("2")) {
                System.out.print("Размер списка: ");
                int size = Integer.parseInt(scanner.nextLine().trim());
                System.out.print("Минимальное значение: ");
                int minVal = Integer.parseInt(scanner.nextLine().trim());
                System.out.print("Максимальное значение: ");
                int maxVal = Integer.parseInt(scanner.nextLine().trim());
                List<Integer> data = generateRandomList(size, minVal, maxVal);
                System.out.println("Сгенерированный список: " + data);
                Map<String, Map.Entry<List<Integer>, Double>> results = new LinkedHashMap<>();
                System.out.println("\nВыберите алгоритм (или all для всех):");
                for (Map.Entry<String, Algorithm> entry : ALGORITHMS.entrySet()) {
                    System.out.println(entry.getKey() + ". " + entry.getValue().name);
                }
                System.out.print("Ваш выбор: ");
                String algoChoice = scanner.nextLine().trim();
                if (algoChoice.equals("all")) {
                    for (Algorithm alg : ALGORITHMS.values()) {
                        Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                        results.put(alg.name, res);
                    }
                } else if (ALGORITHMS.containsKey(algoChoice)) {
                    Algorithm alg = ALGORITHMS.get(algoChoice);
                    Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                    results.put(alg.name, res);
                } else {
                    System.out.println("Неверный выбор.");
                    continue;
                }
                printTable(results);
            } else if (choice.equals("3")) {
                System.out.print("Введите числа через пробел (или оставьте пустым для случайных): ");
                String input = scanner.nextLine().trim();
                List<Integer> data;
                if (input.isEmpty()) {
                    System.out.print("Размер случайного списка: ");
                    int size = Integer.parseInt(scanner.nextLine().trim());
                    data = generateRandomList(size, 1, 100);
                    System.out.println("Сгенерированный список: " + data);
                } else {
                    data = new ArrayList<>();
                    for (String p : input.split("\\s+")) {
                        try { data.add(Integer.parseInt(p)); } catch (NumberFormatException e) {}
                    }
                }
                if (data.isEmpty()) { System.out.println("Список пуст."); continue; }
                Map<String, Map.Entry<List<Integer>, Double>> results = new LinkedHashMap<>();
                for (Algorithm alg : ALGORITHMS.values()) {
                    Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                    results.put(alg.name, res);
                }
                printTable(results);
                System.out.print("Экспортировать результаты? (y/n): ");
                String export = scanner.nextLine().trim().toLowerCase();
                if (export.equals("y")) {
                    System.out.print("Формат (csv/json): ");
                    String fmt = scanner.nextLine().trim();
                    System.out.print("Имя файла: ");
                    String filename = scanner.nextLine().trim();
                    if (filename.isEmpty()) filename = "results." + fmt;
                    if (fmt.equals("csv")) exportCSV(results, filename);
                    else exportJSON(results, filename);
                    System.out.println("Экспортировано в " + filename);
                }
            } else {
                System.out.println("Неверный выбор.");
            }
        }
    }

    public static void main(String[] args) throws IOException {
        if (args.length > 0) {
            String listFlag = null, algorithm = null, exportCsv = null, exportJson = null;
            boolean compare = false;
            int randomSize = 0;
            for (int i = 0; i < args.length; i++) {
                if (args[i].equals("--list")) {
                    listFlag = args[++i];
                } else if (args[i].equals("--random")) {
                    randomSize = Integer.parseInt(args[++i]);
                } else if (args[i].equals("--algorithm")) {
                    algorithm = args[++i];
                } else if (args[i].equals("--export-csv")) {
                    exportCsv = args[++i];
                } else if (args[i].equals("--export-json")) {
                    exportJson = args[++i];
                } else if (args[i].equals("--compare")) {
                    compare = true;
                }
            }
            List<Integer> data = new ArrayList<>();
            if (listFlag != null) {
                for (String p : listFlag.split(",")) {
                    try { data.add(Integer.parseInt(p.trim())); } catch (NumberFormatException e) {}
                }
            } else if (randomSize > 0) {
                data = generateRandomList(randomSize, 1, 100);
            } else {
                System.out.println("Укажите --list или --random");
                return;
            }
            if (data.isEmpty()) { System.out.println("Список пуст."); return; }
            Map<String, Map.Entry<List<Integer>, Double>> results = new LinkedHashMap<>();
            if (compare || "all".equals(algorithm)) {
                for (Algorithm alg : ALGORITHMS.values()) {
                    Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                    results.put(alg.name, res);
                }
            } else if (ALGORITHMS.containsKey(algorithm)) {
                Algorithm alg = ALGORITHMS.get(algorithm);
                Map.Entry<List<Integer>, Double> res = measureTime(alg.func, data);
                results.put(alg.name, res);
            } else {
                System.out.println("Укажите --algorithm или --compare");
                return;
            }
            printTable(results);
            if (exportCsv != null) { exportCSV(results, exportCsv); System.out.println("Экспортировано в " + exportCsv); }
            if (exportJson != null) { exportJSON(results, exportJson); System.out.println("Экспортировано в " + exportJson); }
        } else {
            interactive();
        }
    }
}
