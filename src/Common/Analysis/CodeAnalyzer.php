<?php

declare(strict_types=1);

namespace AutoGen\Common\Analysis;

use AutoGen\Common\Contracts\CodeAnalyzerInterface;
use AutoGen\Common\Traits\HasConfiguration;
use AutoGen\Common\Traits\HandlesFiles;
use AutoGen\Common\Traits\ValidatesInput;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class CodeAnalyzer implements CodeAnalyzerInterface
{
    use HasConfiguration;
    use HandlesFiles;
    use ValidatesInput;

    protected Parser $parser;
    protected NodeTraverser $traverser;

    /**
     * Create a new code analyzer instance.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->initializeParser();
    }

    /**
     * Analyze a file and return analysis results.
     */
    public function analyzeFile(string $filePath): array
    {
        $this->validateFilePath($filePath);
        
        if (!$this->hasAllowedExtension($filePath, ['php'])) {
            return ['error' => 'Unsupported file type'];
        }

        $code = $this->readFile($filePath);
        
        return array_merge(
            $this->analyzeCode($code),
            ['file_path' => $filePath]
        );
    }

    /**
     * Analyze code string and return analysis results.
     */
    public function analyzeCode(string $code, string $fileType = 'php'): array
    {
        $this->validateNotEmpty($code, 'code');

        if ($fileType !== 'php') {
            return ['error' => 'Only PHP analysis is currently supported'];
        }

        try {
            $ast = $this->parser->parse($code);
            
            if ($ast === null) {
                return ['error' => 'Failed to parse code'];
            }

            return [
                'metrics' => $this->getMetrics($code),
                'quality_issues' => $this->checkQuality($code),
                'code_smells' => $this->detectCodeSmells($code),
                'complexity' => $this->getComplexityAnalysis($code),
                'psr_compliance' => $this->checkPSRCompliance($code),
                'ast' => $this->getASTSummary($ast),
            ];
        } catch (Error $e) {
            return [
                'error' => 'Parse error: ' . $e->getMessage(),
                'line' => $e->getStartLine(),
            ];
        }
    }

    /**
     * Analyze a directory and return analysis results.
     */
    public function analyzeDirectory(string $directoryPath): array
    {
        $this->validateFilePath($directoryPath);
        
        $phpFiles = $this->getFilesInDirectory($directoryPath, ['php']);
        $results = [
            'directory' => $directoryPath,
            'total_files' => count($phpFiles),
            'files' => [],
            'summary' => [
                'total_lines' => 0,
                'total_methods' => 0,
                'total_classes' => 0,
                'average_complexity' => 0,
                'issues_count' => 0,
            ],
        ];

        foreach ($phpFiles as $file) {
            $analysis = $this->analyzeFile($file);
            $results['files'][] = $analysis;
            
            // Aggregate summary data
            if (!isset($analysis['error'])) {
                $results['summary']['total_lines'] += $analysis['metrics']['lines_of_code'] ?? 0;
                $results['summary']['total_methods'] += $analysis['metrics']['methods_count'] ?? 0;
                $results['summary']['total_classes'] += $analysis['metrics']['classes_count'] ?? 0;
                $results['summary']['issues_count'] += count($analysis['quality_issues'] ?? []);
            }
        }

        // Calculate averages
        if ($results['total_files'] > 0) {
            $results['summary']['average_complexity'] = 
                ($results['summary']['total_methods'] > 0) 
                    ? round($results['summary']['total_lines'] / $results['summary']['total_methods'], 2)
                    : 0;
        }

        return $results;
    }

    /**
     * Get code metrics for the given code.
     */
    public function getMetrics(string $code): array
    {
        $lines = explode("\n", $code);
        $nonEmptyLines = array_filter($lines, fn($line) => trim($line) !== '');
        $commentLines = array_filter($lines, fn($line) => preg_match('/^\s*\/\/|^\s*\/\*|\s*\*/', $line));

        return [
            'total_lines' => count($lines),
            'lines_of_code' => count($nonEmptyLines),
            'comment_lines' => count($commentLines),
            'blank_lines' => count($lines) - count($nonEmptyLines),
            'classes_count' => substr_count($code, 'class '),
            'methods_count' => substr_count($code, 'function '),
            'interfaces_count' => substr_count($code, 'interface '),
            'traits_count' => substr_count($code, 'trait '),
        ];
    }

    /**
     * Check code quality and return issues.
     */
    public function checkQuality(string $code): array
    {
        $issues = [];

        // Basic quality checks
        if (strpos($code, 'var_dump') !== false) {
            $issues[] = ['type' => 'debug', 'message' => 'Debug function var_dump found'];
        }

        if (strpos($code, 'die(') !== false || strpos($code, 'exit(') !== false) {
            $issues[] = ['type' => 'flow', 'message' => 'Hard exit found (die/exit)'];
        }

        if (preg_match('/\$[A-Z]/', $code)) {
            $issues[] = ['type' => 'naming', 'message' => 'Variable names should be camelCase'];
        }

        // Check for long methods (basic heuristic)
        preg_match_all('/function\s+\w+.*?\{(.*?)\}/s', $code, $matches);
        foreach ($matches[1] as $methodBody) {
            $methodLines = count(explode("\n", trim($methodBody)));
            if ($methodLines > 50) {
                $issues[] = ['type' => 'complexity', 'message' => 'Method exceeds 50 lines'];
            }
        }

        return $issues;
    }

    /**
     * Detect code smells and anti-patterns.
     */
    public function detectCodeSmells(string $code): array
    {
        $smells = [];

        // God object (many methods)
        $methodCount = substr_count($code, 'function ');
        if ($methodCount > 20) {
            $smells[] = ['type' => 'god_object', 'message' => "Class has {$methodCount} methods (consider splitting)"];
        }

        // Long parameter lists
        if (preg_match('/function\s+\w+\([^)]{100,}/', $code)) {
            $smells[] = ['type' => 'long_parameter_list', 'message' => 'Long parameter list detected'];
        }

        // Duplicate code
        $lines = explode("\n", $code);
        $lineCounts = array_count_values(array_map('trim', $lines));
        foreach ($lineCounts as $line => $count) {
            if ($count > 3 && strlen($line) > 20 && !empty($line)) {
                $smells[] = ['type' => 'duplicate_code', 'message' => "Duplicate line found {$count} times: " . substr($line, 0, 50)];
            }
        }

        return $smells;
    }

    /**
     * Get complexity analysis for the given code.
     */
    public function getComplexityAnalysis(string $code): array
    {
        // Simple cyclomatic complexity calculation
        $complexity = 1; // Base complexity
        
        // Add complexity for control structures
        $complexity += substr_count($code, 'if ');
        $complexity += substr_count($code, 'else if ');
        $complexity += substr_count($code, 'elseif ');
        $complexity += substr_count($code, 'while ');
        $complexity += substr_count($code, 'for ');
        $complexity += substr_count($code, 'foreach ');
        $complexity += substr_count($code, 'switch ');
        $complexity += substr_count($code, 'case ');
        $complexity += substr_count($code, 'catch ');
        $complexity += substr_count($code, '&&');
        $complexity += substr_count($code, '||');

        return [
            'cyclomatic_complexity' => $complexity,
            'complexity_rating' => $this->getComplexityRating($complexity),
            'maintainability_index' => $this->calculateMaintainabilityIndex($code),
        ];
    }

    /**
     * Check PSR compliance.
     */
    public function checkPSRCompliance(string $code): array
    {
        $issues = [];

        // PSR-1 checks
        if (!preg_match('/^<\?php/', $code)) {
            $issues[] = ['psr' => 'PSR-1', 'message' => 'Files should start with <?php tag'];
        }

        // PSR-2/PSR-12 checks
        if (preg_match('/\t/', $code)) {
            $issues[] = ['psr' => 'PSR-12', 'message' => 'Use spaces instead of tabs for indentation'];
        }

        if (preg_match('/\r\n/', $code)) {
            $issues[] = ['psr' => 'PSR-12', 'message' => 'Use Unix line endings (LF)'];
        }

        // Check for proper class naming
        if (preg_match('/class\s+([a-z])/', $code)) {
            $issues[] = ['psr' => 'PSR-1', 'message' => 'Class names should be in StudlyCaps'];
        }

        return $issues;
    }

    /**
     * Initialize the PHP parser.
     */
    protected function initializeParser(): void
    {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
    }

    /**
     * Get AST summary.
     */
    protected function getASTSummary(array $ast): array
    {
        return [
            'node_count' => count($ast),
            'depth' => $this->calculateASTDepth($ast),
        ];
    }

    /**
     * Calculate AST depth.
     */
    protected function calculateASTDepth(array $nodes, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;
        
        // This is a simplified depth calculation
        // In a real implementation, you'd traverse the actual AST nodes
        
        return $maxDepth;
    }

    /**
     * Get complexity rating.
     */
    protected function getComplexityRating(int $complexity): string
    {
        if ($complexity <= 10) return 'Low';
        if ($complexity <= 20) return 'Moderate';
        if ($complexity <= 50) return 'High';
        return 'Very High';
    }

    /**
     * Calculate maintainability index.
     */
    protected function calculateMaintainabilityIndex(string $code): float
    {
        $metrics = $this->getMetrics($code);
        $complexity = $this->getComplexityAnalysis($code);
        
        // Simplified maintainability index calculation
        $loc = $metrics['lines_of_code'];
        $cc = $complexity['cyclomatic_complexity'];
        
        if ($loc === 0) return 100.0;
        
        $mi = max(0, (171 - 5.2 * log($loc) - 0.23 * $cc) * 100 / 171);
        
        return round($mi, 2);
    }
}