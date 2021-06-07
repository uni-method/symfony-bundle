<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UniMethod\Bundle\Model\Filters;
use UniMethod\Bundle\Model\Page;

class QueryResolver
{
    protected Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException('Please provide request stack');
        }

        $this->request = $request;
    }

    /**
     * @return Page
     */
    public function extractPage(): Page
    {
        $rawPage = $this->request->query->all('page');

        $page = new Page();
        $page->limit = (int) ($rawPage['limit'] ?? Page::DEFAULT_LIMIT);
        $page->offset = (int) ($rawPage['offset'] ?? Page::DEFAULT_OFFSET);

        return $page;
    }

    public function extractFilter(): Filters
    {
        $rawFilter = $this->request->query->all('filter');

        $filters = new Filters();
        $filters->params = [];

        return $filters;
    }
}
