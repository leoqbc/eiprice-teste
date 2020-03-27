<?php
/**
 *  Created by Leonardo Tumadjian 
 */
namespace Bot;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Goutte\Client;
use League\Csv\Writer;
use League\Csv\Reader;
use Symfony\Component\HttpClient\HttpClient;

use Bot\Collections\PriceList;
use Bot\Filter;

/**
 * Classe de execução do comando
 */
class PriceFinderCommand extends Command
{
    protected static $defaultName = 'search';

    protected $csv;

    protected $precoRef;

    protected $priceList;

    /**
     * Método para configuração inicial
     */
    protected function configure()
    {
        $this
            ->setDescription('Busca de preços')
            ->setHelp('Busca por EAN uma lista de preço de produtos no google shop.');

        /**
         * Inicializa o gravador de CSV Resumo
         */
        $this->resume = Writer::createFromString();
        $this->resume->setDelimiter(';');
        $this->resume->setOutputBOM(Reader::BOM_UTF8);

        /**
         * Define timezone padrão
         */
        date_default_timezone_set('America/Sao_Paulo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $produtos = require __DIR__ . '/../produtos.php';

        // Inicializa o arquivo de resumo
        $csv_resumo_filename = 'relatorio_eiprice_resumo.csv';
        $this->startProductResume($csv_resumo_filename);

        // ========= inicio do loop de precos e resumo ============
        foreach ($produtos as $produto) {
            $produto_nome = $produto['produto'];

            $output->writeln("Consultando o produto <info>$produto_nome</info>");

            $csv_detalhes_filename = $this->mountProductDetails($produto);

            $output->writeln("<comment>Planilha criada $csv_detalhes_filename</comment>");
            $output->writeln('---');

            $csv_resumo_filename = $this->mountProductResume();

            $output->writeln("<comment>Resumo do Produto adicionado em $csv_resumo_filename</comment>");
            $output->writeln('===');
        } // ========= final do loop ================

        $this->endProductResume($csv_resumo_filename);

        $output->writeln('======== ========');
        $output->writeln("<info>Resumo Finalizado</info>");

        return 0;
    }

    /**
     * Método responsável por acessar a página shop do google
     */
    protected function mountProductDetails(array $produto)
    {
        // Inicializa csv para lista de produtos
        $this->csv = Writer::createFromString();
        $this->csv->setDelimiter(';');
        $this->csv->setOutputBOM(Reader::BOM_UTF8);

        $this->precoRef = $produto['precoRef'];
        $this->priceList = new PriceList($produto['produto'], $this->precoRef, $produto['ean']);

        $produtoEan = $produto['ean'];

        $csv_detalhes = "relatorio_eiprice_detalhes_$produtoEan.csv";

        // Cliente do Gutte/webcrawler
        $client = new Client();

        $crawler = $client->request('GET', sprintf('https://www.google.com/search?output=search&tbm=shop&q=%s&oq=%s', $produtoEan, $produtoEan));

        $result = $crawler->filter('html a')->reduce(function ($node) {
            if(preg_match('#^comparar preços#iu', $node->text())) {
                return true;
            }
            return false;
        });

        $link = $result->filter('a')->link();

        $listPage = $client->click($link);

        // Limpa o csv caso já exista
        $this->clearCsv($csv_detalhes);
        $this->putCsvVendedoresHead();

        /** 
         *  Carrega lista de preços primeira página 
         */
        $priceList = $this->getPriceListPerPage($listPage);

        /** 
         *  Adiciona no CSV
         *  Primeiras 5 empresas 
         */
        $this->mountPriceList($priceList);

        $link = $listPage->selectLink('Mais >')->link();
        $listPage2 = $client->click($link);

        /** 
         *  Carrega lista de preços segunda página 
         */
        $priceList = $this->getPriceListPerPage($listPage2);

        /** 
         *  Adiciona no CSV
         *  Próximas 5 empresas 
         */
        $this->mountPriceList($priceList);

        /**
         * Grava o arquivo CSV com a lista completa
         */
        file_put_contents($csv_detalhes, $this->csv->getContent());

        return $csv_detalhes;
    }

    protected function mountPriceList($priceList)
    {
        $priceList->each(function ($node, $i) {
            if ($i >= 5) {
                return false;
            }
            $price = $node->filter('div b')->text();
            $company = $node->filter('a')->text();

            $this->priceList->addPrice($company, Filter::normalizeNumber($price));
            
            $this->putCsvLine($company, $price);
        });
    }

    protected function getPriceListPerPage($page)
    {
        $priceList = $page->filter('html div#online > div')->reduce(function ($node, $i) {
            if ($i <= 1) {
                return false;
            }
            return true;
        });

        return $priceList;
    }

    /**
     * Adiciona empresa e preço no csv virtual
     * Classe Vendedor represanta uma linha do csv detalhes
     */
    protected function putCsvLine($company, $price)
    {
        $vendedor = new Vendedor($company, $this->precoRef, $price);
        $this->csv->insertOne($vendedor->getArrayLine());
    }

    protected function putCsvVendedoresHead()
    {
        $this->csv->insertOne([
            'VENDEDOR', 
            'PREÇO REF', 
            'PREÇO CANAL',
            'GAP',
            'STATUS',
            'DATA',
            'HORA'
        ]);
    }

    protected function startProductResume($csv_resumo_filename)
    {
        $this->clearCsv($csv_resumo_filename);
        $this->resume->insertOne([
            'PRODUTO',
            'EAN',
            'PRECO REF',
            'MIN',
            'MAX',
            'MEDIA',
            'QTDE SELLER',
            'DATA'
        ]);
    }

    /**
     * Método responsável por montar o Resumo
     * ao sim de cada montagem de detalhes
     * 
     * Classe PriceList responsável por montar o resumo
     */
    protected function mountProductResume()
    {
        $this->resume->insertOne($this->priceList->getArrayLine());
        return 'relatorio_eiprice_resumo.csv';
    }

    protected function endProductResume($file_name)
    {
        file_put_contents($file_name, $this->resume->getContent());
    }

    protected function clearCsv($file_name)
    {
        file_put_contents($file_name, '');
    }
}