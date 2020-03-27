<?php
/**
 *  Created by Leonardo Tumadjian 
 */
namespace Bot;

/**
 * Classe reservada para filtros de dados
 */
class Filter
{
    /**
     * Limpa entrada para numero flutuante
     * ex: R$ 12.999,99 > 12999.99
     */
    public static function normalizeNumber(string $number): float
    {
        $numberSanitized = filter_var($number, FILTER_SANITIZE_NUMBER_INT);
        return $numberSanitized / 100;
    }
}