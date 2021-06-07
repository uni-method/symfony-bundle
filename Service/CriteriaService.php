<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Doctrine\Common\Collections\Criteria;
use UniMethod\Bundle\Model\Filters;
use UniMethod\Bundle\Model\Page;

class CriteriaService
{
    public function getCriteriaForPage(Page $page): Criteria
    {
        $criteria = Criteria::create();
        $criteria->setMaxResults($page->limit)->setFirstResult($page->offset);

        return $criteria;
    }

    /**
     * @param Filters $filters
     * @return Criteria[]
     */
    public function getC(Filters $filters): array
    {
        return [];
    }
}
