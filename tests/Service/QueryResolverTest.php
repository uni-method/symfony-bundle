<?php declare(strict_types=1);

namespace UniMethod\Bundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UniMethod\Bundle\Model\Page;
use UniMethod\Bundle\Service\QueryResolver;

class QueryResolverTest extends TestCase
{
    /**
     * @dataProvider providerExtractPage
     * @param array $query
     * @param int $limit
     * @param int $offset
     */
    public function testExtractPage(array $query, int $limit, int $offset): void
    {
        $requestStack = new RequestStack;
        $request = new Request($query, []);
        $requestStack->push($request);
        $queryResolver = new QueryResolver($requestStack);
        $page = $queryResolver->extractPage();

        self::assertEquals($limit, $page->limit);
        self::assertEquals($offset, $page->offset);
    }

    /**
     * @return array[]
     */
    protected function providerExtractPage(): array
    {
        return [
            'existed' => [
                'query' => [
                    'page' => [
                        'limit' => 2,
                        'offset' => '3',
                    ]
                ],
                'limit' => 2,
                'offset' => 3,
            ],
            'non existed' => [
                'query' => [],
                'limit' => Page::DEFAULT_LIMIT,
                'offset' => Page::DEFAULT_OFFSET,
            ],
            'partial' => [
                'query' => [
                    'page' => [
                        'limit' => 2,
                    ]
                ],
                'limit' => 2,
                'offset' => Page::DEFAULT_OFFSET,
            ],
        ];
    }
}
