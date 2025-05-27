<?php
class ColorInterpolator
{
    public static function interpolate(array $startColor, array $endColor, float $ratio): array
    {
        return [
            'r' => $startColor['r'] + ($endColor['r'] - $startColor['r']) * $ratio,
            'g' => $startColor['g'] + ($endColor['g'] - $startColor['g']) * $ratio,
            'b' => $startColor['b'] + ($endColor['b'] - $startColor['b']) * $ratio,
        ];
    }

    public static function allocateColor($image, array $rgb, float $opacity): int
    {
        $alpha = 127 - (int)(($opacity / 100) * 127);
        return imagecolorallocatealpha($image, $rgb['r'], $rgb['g'], $rgb['b'], $alpha);
    }
}

