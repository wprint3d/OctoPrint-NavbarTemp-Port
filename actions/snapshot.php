<?php

$autoloadPath = getenv('WPRINT3D_VENDOR_AUTOLOAD');

if ($autoloadPath && is_file($autoloadPath)) {
    require_once $autoloadPath;
}

$bootstrapPath = getenv('WPRINT3D_BOOTSTRAP_APP');

if ($bootstrapPath && is_file($bootstrapPath)) {
    $app = require $bootstrapPath;
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
}

$payload = json_decode(stream_get_contents(STDIN), true) ?? [];
$storedSettings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
$overrideSettings = is_array($payload['payload']['settings'] ?? null) ? $payload['payload']['settings'] : [];
$settings = array_merge($storedSettings, $overrideSettings);
$previewOnly = (bool) ($payload['payload']['previewOnly'] ?? false);
$printerId = $payload['context']['printerId'] ?? null;

$items = [];

if (($settings['displayRaspiTemp'] ?? true) === true) {
    $temperature = (new \App\Plugins\Support\SbcTemperatureReader())->read();

    if ($temperature !== null) {
        $label = (string) ($settings['soc_name'] ?? 'SoC');
        $items[] = [
            'id' => 'soc',
            'icon' => 'chip',
            'label' => $label,
            'text' => format_label($label, sprintf_temperature($temperature, $settings)),
            'displayValue' => sprintf_temperature($temperature, $settings),
            'value' => $temperature,
            'targetValue' => null,
            'min' => 0,
            'max' => 100,
        ];
    }
}

if ($printerId) {
    $printer = \App\Models\Printer::find($printerId);
    $statistics = $printer?->getStatistics() ?? [];

    foreach (array_values($statistics['extruders'] ?? []) as $index => $extruder) {
        $actual = normalize_temperature($extruder['temperature'] ?? null);

        if ($actual === null) {
            continue;
        }

        $target = normalize_temperature($extruder['target'] ?? null);
        $label = tool_label($index, $settings);
        $items[] = [
            'id' => 'tool-'.$index,
            'icon' => 'printer-3d-nozzle',
            'text' => format_tool_temperature($index, $actual, $target, $settings),
            'label' => $label,
            'displayValue' => sprintf_temperature($actual, $settings),
            'value' => $actual,
            'targetValue' => $target,
            'min' => 0,
            'max' => $target !== null && $target > 0 ? max($target, $actual) : 300,
        ];
    }

    $bed = $statistics['bed'] ?? null;
    $bedActual = normalize_temperature($bed['temperature'] ?? null);

    if ($bedActual !== null) {
        $bedTarget = normalize_temperature($bed['target'] ?? null);
        $items[] = [
            'id' => 'bed',
            'icon' => 'radiator',
            'label' => 'Bed',
            'text' => format_named_temperature('Bed', $bedActual, $bedTarget, $settings),
            'displayValue' => sprintf_temperature($bedActual, $settings),
            'value' => $bedActual,
            'targetValue' => $bedTarget,
            'min' => 0,
            'max' => $bedTarget !== null && $bedTarget > 0 ? max($bedTarget, $bedActual) : 120,
        ];
    }
}

$customCommand = trim((string) ($settings['cmd'] ?? ''));
$customCommandName = trim((string) ($settings['cmd_name'] ?? ''));

if ($customCommand !== '' && $customCommandName !== '') {
    $commandResult = read_custom_command($customCommand);

    if ($commandResult !== null && $commandResult !== '') {
        $customItem = [
            'id' => 'custom-command',
            'icon' => 'console',
            'label' => $customCommandName,
            'text' => format_label($customCommandName, $commandResult),
            'displayValue' => $commandResult,
            'targetValue' => null,
        ];

        if (is_numeric($commandResult)) {
            $customItem['value'] = (float) $commandResult;
            $customItem['min'] = 0;
            $customItem['max'] = 100;
        }

        $items[] = $customItem;
    }
}

$data = [
    'items' => $items,
    'printerId' => $printerId,
    'updatedAt' => now()->toAtomString(),
];

$response = [
    'data' => $data,
];

if (! $previewOnly) {
    $response['effects'] = [
        [
            'type' => 'send_plugin_message',
            'merge' => false,
            'data' => $data,
        ],
    ];
}

echo json_encode($response);

function normalize_temperature(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    if (! is_numeric($value)) {
        return null;
    }

    return round((float) $value, 1);
}

function format_tool_temperature(int $index, float $actual, ?float $target, array $settings): string
{
    return format_named_temperature(tool_label($index, $settings), $actual, $target, $settings);
}

function tool_label(int $index, array $settings): string
{
    $useShortNames = (bool) ($settings['useShortNames'] ?? false);

    return $useShortNames ? ($index === 0 ? 'E' : 'E'.$index) : ($index === 0 ? 'Tool' : 'Tool '.$index);
}

function format_named_temperature(string $label, float $actual, ?float $target, array $settings): string
{
    $body = sprintf_temperature($actual, $settings);

    if ($target !== null && $target > 0) {
        $arrow = $target >= $actual ? ' ↗ ' : ' ↘ ';
        $body .= $arrow.sprintf_temperature($target, $settings);
    }

    return format_label($label, $body, (bool) ($settings['makeMoreRoom'] ?? false));
}

function format_label(string $label, string $value, bool $compact = false): string
{
    return $compact
        ? sprintf('%s:%s', trim($label), trim($value))
        : sprintf('%s: %s', trim($label), trim($value));
}

function sprintf_temperature(float $value, array $settings): string
{
    $formatted = sprintf('%.1f°C', $value);

    if (($settings['showFahrenheitAlso'] ?? false) === true) {
        $formatted .= sprintf(' (%.1f°F)', ($value * 9 / 5) + 32);
    }

    return $formatted;
}

function read_custom_command(string $command): ?string
{
    $output = @shell_exec($command);

    if (! is_string($output)) {
        return null;
    }

    $normalized = preg_replace('/\s+/', ' ', trim($output));

    return $normalized === '' ? null : $normalized;
}
