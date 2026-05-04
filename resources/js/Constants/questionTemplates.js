export const LANGUAGE_TEMPLATES = {
    javascript: "function solution(a, b) {\n    return a + b;\n}",
    python: "def solution(a, b):\n    return a + b",
    php: "<?php\n\nfunction solution($a, $b) {\n    return $a + $b;\n}",
    java: "public class Solution {\n    public static int solution(int a, int b) {\n        return a + b;\n    }\n}",
    cpp: "int solution(int a, int b) {\n    return a + b;\n}"
};

export const TEST_TEMPLATES = {
    javascript: "// Exemple de test\nconsole.log(solution(1, 2) === 3 ? 'PASS' : 'FAIL');\nconsole.log(solution(10, 5) === 15 ? 'PASS' : 'FAIL');",
    python: "# Exemple de test\nprint('PASS' if solution(1, 2) == 3 else 'FAIL')\nprint('PASS' if solution(10, 5) == 15 else 'FAIL')",
    php: "// Exemple de test\necho solution(1, 2) === 3 ? 'PASS' : 'FAIL';\necho solution(10, 5) === 15 ? 'PASS' : 'FAIL';",
    java: "// Pour Java, les tests doivent être dans une méthode main d'une classe Main\npublic class Main {\n    public static void main(String[] args) {\n        System.out.println(Solution.solution(1, 2) == 3 ? \"PASS\" : \"FAIL\");\n        System.out.println(Solution.solution(10, 5) == 15 ? \"PASS\" : \"FAIL\");\n    }\n}",
    cpp: "#include <iostream>\n\nint main() {\n    // Exemple de test\n    std::cout << (solution(1, 2) == 3 ? \"PASS\" : \"FAIL\") << std::endl;\n    std::cout << (solution(10, 5) == 15 ? \"PASS\" : \"FAIL\") << std::endl;\n    return 0;\n}"
};
