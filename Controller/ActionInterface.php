<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ActionInterface
{
    public function action(): JsonResponse;
}
