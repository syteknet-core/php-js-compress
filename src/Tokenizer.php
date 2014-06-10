<?php

/*
 * This file is part of HtmlCompress.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WyriHaximus\HtmlCompress;

use \WyriHaximus\HtmlCompress\Compressor\CompressorInterface;

/**
 * Class Parser
 *
 * @package WyriHaximus\HtmlCompress
 */
class Tokenizer {

    public static function tokenize($html, array $compressors, CompressorInterface $defaultCompressor = null) {
        $self = new self($compressors, $defaultCompressor);
        return $self->parse($html);
    }

    public function __construct(array $compressors, CompressorInterface $defaultCompressor = null) {
        $this->compressors = $compressors;
        $this->defaultCompressor = $defaultCompressor;
    }

    public function parse($html) {
        $tokens = [
            [
                'html' => $html,
                'compressor'=> $this->defaultCompressor,
            ]
        ];
        do {
            $compressor = array_shift($this->compressors);
            $tokens = $this->split($tokens, $compressor);
        } while (count($this->compressors) > 0);
        return $tokens;
    }

    protected function split(array $tokens, array $compressor) {
        foreach ($compressor['patterns'] as $pattern) {
            foreach ($tokens as $index => $token) {
                if ($token['compressor'] === $this->defaultCompressor) {
                    $html = preg_split($pattern, $token['html']);
                    preg_match_all($pattern, $token['html'], $bits);

                    $newTokens = $this->walkBits($bits, $html, $compressor);
                    $tokens = $this->replaceToken($tokens, $index, $newTokens);
                }
            }
        }

        return $tokens;
    }

    protected function walkBits($bits, $html, $compressor) {
        $newTokens = [];
        $prepend = '';
        for ($i = 0; $i < count($bits[0]); $i++) {
            $newTokens[] = [
                'html' => $prepend . $html[$i] . $bits[1][$i],
                'compressor'=> $this->defaultCompressor,
            ];
            $newTokens[] = [
                'html' => $bits[2][$i],
                'compressor'=> $compressor['compressor'],
            ];
            $prepend = $bits[3][$i];
        }

        $newTokens[] = [
            'html' => $prepend . $html[$i],
            'compressor'=> $this->defaultCompressor,
        ];

        return $newTokens;
    }

    protected function replaceToken($tokens, $index, $newTokens) {
        return array_merge(array_slice($tokens, 0, $index), $newTokens, array_slice($tokens, $index + 1));
    }

}