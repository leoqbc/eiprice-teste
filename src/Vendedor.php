<?php
/**
 *  Created by Leonardo Tumadjian 
 */
namespace Bot;

use Bot\Filters;

class Vendedor
{
    protected $nome;
    
    protected $precoRef;
    
    protected $precoCanal;
    
    protected $data;
    
    protected $hora;

    public function __construct($nome, $precoRef, $precoCanal)
    {
        $this->setNome($nome);
        $this->setPrecoRef($precoRef);
        $this->setPrecoCanal($precoCanal);
        $this->setData();
        $this->setHora();
    }

    public function getNome()
    {
        return $this->nome;
    }
    
    public function setNome($nome)
    {
            $this->nome = $nome;
    }
    

    public function getPrecoRef()
    {
        return $this->precoRef;
    }
    
    public function setPrecoRef(float $precoRef)
    {
        $this->precoRef = $precoRef;
    }
    
    public function getPrecoCanal()
    {
        return $this->precoCanal;
    }
    
    public function setPrecoCanal(string $precoCanal)
    {
        $this->precoCanal = Filter::normalizeNumber($precoCanal);
    }
    

    public function getGap()
    {
        return $this->precoRef - $this->precoCanal;
    }

    public function getStatus()
    {
        if ($this->precoRef > $this->precoCanal) {
            return 'MAIS CARO';
        }
        if ($this->precoRef === $this->precoCanal) {
            return 'IGUAL';
        }
        return 'MAIS BARATO';
    }

    public function getData()
    {
        return $this->data;
    }
    
    public function setData()
    {
        $this->data = date('d/m/Y');
    }

    public function getHora()
    {
        return $this->hora;
    }
    
    public function setHora()
    {
        $this->hora = date('H:i:s');
    }

    public function getArrayLine(): array
    {
        return [
            $this->getNome(),
            $this->getPrecoRef(),
            $this->getPrecoCanal(),
            $this->getGap(),
            $this->getStatus(),
            $this->getData(),
            $this->getHora()
        ];
    }
}

