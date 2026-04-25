<?php

declare(strict_types=1);

namespace Catch\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class PublishSkillsCommand extends Command
{
    protected $signature = 'catch:publish:skills';

    protected $description = '发布 CatchAdmin Skills 到 AI 编程平台';

    /**
     * 平台配置: name => [label, type]
     *
     * 类型:
     *  agent-skills   — 复制完整目录 (SKILL.md + references/ + assets/)
     *  single-file    — 合并 SKILL.md 内容 + references 为单文件
     */
    protected array $platforms = [
        'claude' => [
            'label' => 'Claude Code',
            'type' => 'agent-skills',
        ],
        'cursor' => [
            'label' => 'Cursor',
            'type' => 'single-file',
        ],
        'codex' => [
            'label' => 'Codex',
            'type' => 'agent-skills',
        ],
        'gemini' => [
            'label' => 'Gemini CLI',
            'type' => 'single-file',
        ],
        'copilot' => [
            'label' => 'GitHub Copilot',
            'type' => 'single-file',
        ],
        'junie' => [
            'label' => 'Junie',
            'type' => 'agent-skills',
        ],
        'kiro' => [
            'label' => 'Kiro',
            'type' => 'single-file',
        ],
        'windsurf' => [
            'label' => 'Windsurf',
            'type' => 'agent-skills',
        ],
    ];

    /**
     * 单文件平台的 skill 发布策略
     *
     * @var array<string, array{include_assets: bool}>
     */
    protected array $singleFileSkillProfiles = [
        'default' => [
            'include_assets' => false,
        ],
        'catchadmin-module' => [
            'include_assets' => true,
        ],
    ];

    public function handle(): int
    {
        $skillsSource = $this->getSkillsSourcePath();

        if (! is_dir($skillsSource)) {
            warning('Skills 源目录不存在: ' . $skillsSource);

            return self::FAILURE;
        }

        $skills = $this->discoverSkills($skillsSource);
        if (empty($skills)) {
            warning('未找到任何 Skill: ' . $skillsSource);

            return self::FAILURE;
        }

        $selectedPlatforms = multiselect(
            label: '选择目标平台',
            options: array_map(fn ($p) => $p['label'], $this->platforms),
            required: '请至少选择一个平台',
            hint: '空格选择，回车确认',
        );

        // 检测是否存在冲突文件，仅在有冲突时询问是否覆盖
        if ($this->hasExistingTargets($selectedPlatforms, $skills)) {
            if (! confirm(label: '检测到已有文件，是否全部覆盖？', default: false)) {
                return self::SUCCESS;
            }
        }

        $published = 0;

        foreach ($selectedPlatforms as $platform) {
            if ($platform === 'gemini' && $this->platforms[$platform]['type'] === 'single-file') {
                if ($this->publishGeminiFile($skills)) {
                    $published += count($skills);
                }

                continue;
            }

            foreach ($skills as $skillName => $skillPath) {
                $result = match ($this->platforms[$platform]['type']) {
                    'agent-skills' => $this->publishAgentSkills($platform, $skillName, $skillPath),
                    'single-file' => $this->publishSingleFile($platform, $skillName, $skillPath),
                };

                if ($result) {
                    $published++;
                }
            }
        }

        info("✓ 成功发布 {$published} 个 Skill");

        return self::SUCCESS;
    }

    /**
     * 获取 Skills 源目录路径
     */
    protected function getSkillsSourcePath(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'skills';
    }

    /**
     * 自动发现源目录中的所有 Skill
     *
     * @return array<string, string> skillName => skillPath
     */
    protected function discoverSkills(string $sourcePath): array
    {
        $skills = [];

        foreach (File::directories($sourcePath) as $dir) {
            $skillMd = $dir . DIRECTORY_SEPARATOR . 'SKILL.md';
            if (File::exists($skillMd)) {
                $skills[basename($dir)] = $dir;
            }
        }

        return $skills;
    }

    /**
     * 检测选中平台是否已存在目标文件或目录
     *
     * @param  string[]  $platforms
     * @param  array<string, string>  $skills
     */
    protected function hasExistingTargets(array $platforms, array $skills): bool
    {
        foreach ($platforms as $platform) {
            if ($platform === 'gemini' && File::exists($this->getGeminiTargetFile())) {
                return true;
            }

            foreach ($skills as $skillName => $skillPath) {
                $type = $this->platforms[$platform]['type'];

                if ($type === 'agent-skills') {
                    $targetDir = match ($platform) {
                        'windsurf' => $this->getUserHomePath() . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName,
                        'claude' => base_path('.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
                        'codex' => base_path('.codex' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
                        'junie' => base_path('.junie' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
                    };

                    if (File::isDirectory($targetDir)) {
                        return true;
                    }
                } else {
                    [, $targetFile] = match ($platform) {
                        'cursor' => $this->buildCursorOutput($skillName, $skillPath),
                        'copilot' => $this->buildCopilotOutput($skillName, $skillPath),
                        'kiro' => $this->buildKiroOutput($skillName, $skillPath),
                    };

                    if (File::exists($targetFile)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 以 Agent Skills 标准发布（Claude Code、Codex、Junie、Windsurf）
     */
    protected function publishAgentSkills(string $platform, string $skillName, string $skillPath): bool
    {
        $targetDir = match ($platform) {
            'windsurf' => $this->getUserHomePath() . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName,
            'claude' => base_path('.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
            'codex' => base_path('.codex' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
            'junie' => base_path('.junie' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . $skillName),
        };

        return $this->copySkillDirectory($skillPath, $targetDir);
    }

    /**
     * 以单文件合并方式发布（Cursor、Gemini CLI、GitHub Copilot、Kiro）
     */
    protected function publishSingleFile(string $platform, string $skillName, string $skillPath): bool
    {
        $profile = $this->getSingleFileSkillProfile($skillName);
        $includeAssets = $profile['include_assets'];

        [$targetDir, $targetFile, $fileContent] = match ($platform) {
            'cursor' => $this->buildCursorOutput($skillName, $skillPath, $includeAssets),
            'copilot' => $this->buildCopilotOutput($skillName, $skillPath, $includeAssets),
            'kiro' => $this->buildKiroOutput($skillName, $skillPath, $includeAssets),
        };

        File::ensureDirectoryExists($targetDir);
        File::put($targetFile, $fileContent);

        return true;
    }

    /**
     * Gemini CLI 使用根级 GEMINI.md 聚合输出
     *
     * @param  array<string, string>  $skills
     */
    protected function publishGeminiFile(array $skills): bool
    {
        [$targetDir, $targetFile, $fileContent] = $this->buildGeminiOutput($skills);

        File::ensureDirectoryExists($targetDir);
        File::put($targetFile, $fileContent);

        return true;
    }

    /**
     * 构建 Cursor .mdc 输出
     *
     * @return array{string, string, string} [targetDir, targetFile, content]
     */
    protected function buildCursorOutput(string $skillName, string $skillPath, bool $includeAssets = false): array
    {
        $targetDir = base_path('.cursor' . DIRECTORY_SEPARATOR . 'rules');
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $skillName . '.mdc';

        $content = $this->buildMergedContent($skillPath, $includeAssets);
        $description = str_replace('"', "'", $this->extractDescription($skillPath));

        $fileContent = "---\ndescription: \"{$description}\"\nalwaysApply: true\n---\n\n{$content}";

        return [$targetDir, $targetFile, $fileContent];
    }

    /**
     * 构建 Gemini CLI GEMINI.md 输出
     *
     * @return array{string, string, string}
     */
    protected function buildGeminiOutput(array $skills): array
    {
        $targetDir = base_path();
        $targetFile = $this->getGeminiTargetFile();

        $sections = ['# CatchAdmin Skills'];
        foreach ($skills as $skillName => $skillPath) {
            $profile = $this->getSingleFileSkillProfile($skillName);
            $sections[] = '## ' . $skillName;
            $sections[] = $this->extractDescription($skillPath);
            $sections[] = $this->buildMergedContent($skillPath, $profile['include_assets']);
        }

        $content = implode("\n\n---\n\n", $sections);

        return [$targetDir, $targetFile, $content];
    }

    /**
     * 构建 GitHub Copilot .instructions.md 输出
     *
     * @return array{string, string, string}
     */
    protected function buildCopilotOutput(string $skillName, string $skillPath, bool $includeAssets = false): array
    {
        $targetDir = base_path('.github' . DIRECTORY_SEPARATOR . 'instructions');
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $skillName . '.instructions.md';

        $content = $this->buildMergedContent($skillPath, $includeAssets);

        $fileContent = "---\napplyTo: '**'\n---\n\n{$content}";

        return [$targetDir, $targetFile, $fileContent];
    }

    /**
     * 构建 Kiro steering .md 输出
     *
     * @return array{string, string, string}
     */
    protected function buildKiroOutput(string $skillName, string $skillPath, bool $includeAssets = false): array
    {
        $targetDir = base_path('.kiro' . DIRECTORY_SEPARATOR . 'steering');
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $skillName . '.md';

        $content = $this->buildMergedContent($skillPath, $includeAssets);
        $fileContent = "---\ninclusion: always\n---\n\n{$content}";

        return [$targetDir, $targetFile, $fileContent];
    }

    /**
     * 复制完整 Skill 目录到目标平台
     */
    protected function copySkillDirectory(string $sourcePath, string $targetDir): bool
    {
        if (File::isDirectory($targetDir)) {
            File::deleteDirectory($targetDir);
        }

        File::copyDirectory($sourcePath, $targetDir);

        return true;
    }

    /**
     * 合并 SKILL.md 正文 + references 为单文件内容
     */
    protected function buildMergedContent(string $skillPath, bool $includeAssets = false): string
    {
        $skillMd = File::get($skillPath . DIRECTORY_SEPARATOR . 'SKILL.md');

        // 去除 YAML frontmatter
        $body = preg_replace('/\A---\s*\n.*?\n---\s*\n/s', '', $skillMd);

        $referencesDir = $skillPath . DIRECTORY_SEPARATOR . 'references';
        $referenceAnchors = [];

        if (File::isDirectory($referencesDir)) {
            foreach (File::files($referencesDir) as $file) {
                $referenceAnchors[$file->getBasename()] = $this->buildReferenceAnchor($file->getBasename());
            }

            $body = $this->rewriteReferenceLinks($body, $referenceAnchors);

            foreach (File::files($referencesDir) as $file) {
                $filename = $file->getBasename();
                $refContent = File::get($file->getPathname());
                $refContent = $this->rewriteReferenceLinks($refContent, $referenceAnchors);

                $body .= "\n\n---\n\n<a id=\"{$referenceAnchors[$filename]}\"></a>\n\n## {$filename}\n\n{$refContent}";
            }
        }

        if ($includeAssets) {
            $assetsDir = $skillPath . DIRECTORY_SEPARATOR . 'assets';
            if (File::isDirectory($assetsDir)) {
                $body .= "\n\n---\n\n# Code Templates\n";

                foreach (File::files($assetsDir) as $file) {
                    $lang = match ($file->getExtension()) {
                        'stub' => 'php',
                        'vue' => 'vue',
                        'ts' => 'typescript',
                        'js' => 'javascript',
                        'md' => 'markdown',
                        default => $file->getExtension(),
                    };
                    $body .= "\n## " . str_replace('.', ' — .', $file->getFilename()) . "\n\n```{$lang}\n"
                        . File::get($file->getPathname()) . "\n```\n";
                }
            }
        }

        return trim($body);
    }

    protected function getSingleFileSkillProfile(string $skillName): array
    {
        return $this->singleFileSkillProfiles[$skillName] ?? $this->singleFileSkillProfiles['default'];
    }

    protected function getGeminiTargetFile(): string
    {
        return base_path('GEMINI.md');
    }

    /**
     * 将 references 文件名转换为稳定锚点
     */
    protected function buildReferenceAnchor(string $filename): string
    {
        return 'reference-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower(pathinfo($filename, PATHINFO_FILENAME)));
    }

    /**
     * 重写指向 references 的相对链接到单文件锚点
     *
     * @param  array<string, string>  $referenceAnchors
     */
    protected function rewriteReferenceLinks(string $content, array $referenceAnchors): string
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $inCodeFence = false;

        foreach ($lines as &$line) {
            if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
                $inCodeFence = ! $inCodeFence;
                continue;
            }

            if ($inCodeFence) {
                continue;
            }

            $line = preg_replace_callback(
                '/^(\s*\[[^\]]+\]:\s*)(<?)([^>\s]+)(>?)(\s+.*)?$/',
                function (array $matches) use ($referenceAnchors): string {
                    $rewrittenTarget = $this->rewriteReferenceTarget($matches[3], $referenceAnchors);

                    if ($rewrittenTarget === null) {
                        return $matches[0];
                    }

                    return $matches[1]
                        . $matches[2]
                        . $rewrittenTarget
                        . $matches[4]
                        . ($matches[5] ?? '');
                },
                $line
            ) ?? $line;

            $line = preg_replace_callback(
                '/\[(.*?)\]\(([^)]+)\)/',
                function (array $matches) use ($referenceAnchors): string {
                    $rewrittenTarget = $this->rewriteReferenceTarget($matches[2], $referenceAnchors);

                    if ($rewrittenTarget !== null) {
                        return '[' . $matches[1] . '](' . $rewrittenTarget . ')';
                    }

                    return $matches[0];
                },
                $line
            ) ?? $line;
        }

        unset($line);

        return implode("\n", $lines);
    }

    /**
     * 重写指向 references 的目标为单文件锚点
     *
     * @param  array<string, string>  $referenceAnchors
     */
    protected function rewriteReferenceTarget(string $target, array $referenceAnchors): ?string
    {
        $target = trim($target);
        $target = trim($target, '<>');

        $fragment = '';
        if (str_contains($target, '#')) {
            [$target, $fragment] = explode('#', $target, 2);
            $fragment = '#' . $fragment;
        }

        $normalizedTarget = str_replace('\\', '/', $target);
        $normalizedTarget = preg_replace('#^(?:\./|\../)+#', '', $normalizedTarget) ?: $normalizedTarget;

        if (
            ! str_starts_with($normalizedTarget, 'references/')
            && ! str_contains($normalizedTarget, '/references/')
        ) {
            return null;
        }

        $basename = basename($normalizedTarget);

        if (! isset($referenceAnchors[$basename])) {
            return null;
        }

        return '#' . $referenceAnchors[$basename] . $fragment;
    }

    /**
     * 从 SKILL.md YAML frontmatter 中提取 description
     */
    protected function extractDescription(string $skillPath): string
    {
        $content = File::get($skillPath . DIRECTORY_SEPARATOR . 'SKILL.md');

        if (preg_match('/^description:\s*(.+?)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'CatchAdmin development skill';
    }

    /**
     * 获取用户主目录路径（跨平台）
     */
    protected function getUserHomePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return getenv('USERPROFILE') ?: getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }

        return getenv('HOME') ?: '/root';
    }
}
