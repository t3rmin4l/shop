<?php

namespace App\Service;

use App\Entity\Product;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearchIndexer
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['elasticsearch:9200'])
            ->build();
    }

    public function indexProduct(Product $product): void
    {
        $this->deleteProduct($product->getId());

        $params = [
            'index' => 'products',
            'id' => $product->getId(),
            'body' => [
                'name' => $product->getProductNameShort(),
                'ean'  => $product->getEanNumber(),
            ],
        ];

        $this->client->index($params);
    }

    public function deleteProduct(int $productId): void
    {
        $this->client->delete([
            'index' => 'products',
            'id' => $productId,
        ]);
    }
}
