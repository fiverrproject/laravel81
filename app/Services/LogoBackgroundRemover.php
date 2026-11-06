<?php

namespace App\Services;

/**
 * Turns an uploaded company logo into a transparent PNG before it is placed on a poster.
 * Solid backgrounds (usually white) are removed. Logos that are already transparent are left as-is.
 */
class LogoBackgroundRemover
{
    public function makeTransparent(string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '' || ! function_exists('imagecreatefromstring')) {
            return $logo;
        }

        $binary = $this->decode($logo);
        if ($binary === null) {
            return $logo;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return $logo;
        }

        if ($this->alreadyTransparent($source)) {
            imagedestroy($source);

            return $logo;
        }

        $source = $this->limitSize($source, 640);
        $background = $this->backgroundColor($source);
        if ($background === null) {
            imagedestroy($source);

            return $logo;
        }

        $transparent = $this->knockOut($source, $background);
        imagedestroy($source);

        ob_start();
        imagepng($transparent);
        $png = ob_get_clean();
        imagedestroy($transparent);

        if (! is_string($png) || $png === '') {
            return $logo;
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

    protected function decode(string $logo): ?string
    {
        if (preg_match('#^data:image/[a-zA-Z0-9.+-]+;base64,([A-Za-z0-9+/=\s]+)$#', $logo, $match) === 1) {
            $binary = base64_decode(preg_replace('/\s+/', '', $match[1]) ?? '', true);

            return is_string($binary) && $binary !== '' ? $binary : null;
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} red, green, blue, alpha (0 opaque, 127 transparent)
     */
    protected function pixel($image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);
        if (imageistruecolor($image)) {
            return [
                ($color >> 16) & 255,
                ($color >> 8) & 255,
                $color & 255,
                ($color >> 24) & 127,
            ];
        }

        $rgba = imagecolorsforindex($image, $color);

        return [
            (int) $rgba['red'],
            (int) $rgba['green'],
            (int) $rgba['blue'],
            (int) ($rgba['alpha'] ?? 0),
        ];
    }

    protected function alreadyTransparent($image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $transparent = 0;
        $seen = 0;
        $stepX = max(1, (int) floor($width / 24));
        $stepY = max(1, (int) floor($height / 24));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $seen++;
                if ($this->pixel($image, $x, $y)[3] > 16) {
                    $transparent++;
                }
            }
        }

        return $seen > 0 && ($transparent / $seen) >= 0.02;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    protected function backgroundColor($image): ?array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $points = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1],
            [(int) ($width / 2), 0],
            [(int) ($width / 2), $height - 1],
            [0, (int) ($height / 2)],
            [$width - 1, (int) ($height / 2)],
        ];

        $samples = [];
        foreach ($points as [$x, $y]) {
            $pixel = $this->pixel($image, $x, $y);
            $samples[] = [$pixel[0], $pixel[1], $pixel[2]];
        }

        $reference = $samples[0];
        $flat = true;
        foreach ($samples as $sample) {
            if ($this->distance($reference, $sample) > 48) {
                $flat = false;
                break;
            }
        }

        if ($flat) {
            return $this->average($samples);
        }

        $nearWhite = 0;
        foreach ($samples as $sample) {
            if ($sample[0] >= 245 && $sample[1] >= 245 && $sample[2] >= 245) {
                $nearWhite++;
            }
        }

        if ($nearWhite >= 6) {
            return [255, 255, 255];
        }

        return null;
    }

    /**
     * @param  list<array{0: int, 1: int, 2: int}>  $samples
     * @return array{0: int, 1: int, 2: int}
     */
    protected function average(array $samples): array
    {
        $count = count($samples);
        $red = 0;
        $green = 0;
        $blue = 0;
        foreach ($samples as $sample) {
            $red += $sample[0];
            $green += $sample[1];
            $blue += $sample[2];
        }

        return [
            (int) round($red / $count),
            (int) round($green / $count),
            (int) round($blue / $count),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $left
     * @param  array{0: int, 1: int, 2: int}  $right
     */
    protected function distance(array $left, array $right): int
    {
        return abs($left[0] - $right[0]) + abs($left[1] - $right[1]) + abs($left[2] - $right[2]);
    }

    protected function limitSize($image, int $max)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= $max && $height <= $max) {
            return $image;
        }

        $scale = $max / max($width, $height);
        $scaled = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        if ($scaled === false) {
            return $image;
        }
        imagedestroy($image);

        return $scaled;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $background
     */
    protected function knockOut($image, array $background)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $output = imagecreatetruecolor($width, $height);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        $clear = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefilledrectangle($output, 0, 0, $width, $height, $clear);

        $hard = 36;
        $soft = 90;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                [$red, $green, $blue] = $this->pixel($image, $x, $y);
                $distance = $this->distance([$red, $green, $blue], $background);

                if ($distance <= $hard) {
                    continue;
                }

                if ($distance < $soft) {
                    $fade = ($soft - $distance) / ($soft - $hard);
                    $keep = 1 - $fade;
                    $red = (int) max(0, min(255, ($red - $background[0] * $fade) / max($keep, 0.001)));
                    $green = (int) max(0, min(255, ($green - $background[1] * $fade) / max($keep, 0.001)));
                    $blue = (int) max(0, min(255, ($blue - $background[2] * $fade) / max($keep, 0.001)));
                    $alpha = (int) round(127 * $fade);
                } else {
                    $alpha = 0;
                }

                $color = imagecolorallocatealpha($output, $red, $green, $blue, $alpha);
                imagesetpixel($output, $x, $y, $color);
            }
        }

        return $output;
    }
}
