<?php
/**
 *  Created by Leonardo Tumadjian 
 */
namespace Bot\Collections;

use Traversable;
use ArrayObject;
use Generator;

/**
 * Classe responsável por montar a tabela de preços
 * em memória e fazer as sumarizações(min, max, media)
 */
class PriceList extends ArrayObject
{
    protected $produtoNome;

    protected $precoRef;

    protected $ean;

    public function __construct($produtoNome, $precoRef, $ean, array $fields = [])
    {
        $this->produtoNome = $produtoNome;
        $this->precoRef = $precoRef;
        $this->ean = $ean;
        parent::__construct($fields);
    }

    public function addPrice($company, $price)
    {
        $this->append([
            'company' => $company,
            'price' => $price
        ]);
    }

    public function getColumn(string $column): Traversable
    {
        return $this->filterField($column);
    }

    public function getIteratorColumn(string $column): array
    {
        $items = new ArrayObject;

        foreach ($this->filterField($column) as $item) {
            $items->append($item);
        }

        return $items->getArrayCopy();
    }

    public function filterField(string $field): Generator
    {
         foreach ($this->getIterator() as $arr) {
             yield $arr[$field];
         }
    }

    public function average(string $field): float
    {
        $total = 0;
        $count = 0;

        foreach ($this->getColumn($field) as $fieldValue) {
            $total += $fieldValue;
            $count++;
        }

        return $total / $count;
    }

    public function min(string $field)
    {
        return min($this->getIteratorColumn($field));
    }

    public function max(string $field)
    {
        return max($this->getIteratorColumn($field));
    }

    public function getArrayLine()
    {
        return [
            'PRODUTO' => $this->produtoNome,
            'EAN' => $this->ean,
            'PRECO REF' => $this->precoRef,
            'MIN' => $this->min('price'),
            'MAX' => $this->max('price'),
            'MEDIA' => $this->average('price'),
            'QTDE SELLER' => $this->count(),
            'DATA' => date('d/m/Y')
        ];
    }
}