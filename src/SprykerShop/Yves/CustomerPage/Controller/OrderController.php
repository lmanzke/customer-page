<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerShop\Yves\CustomerPage\Controller;

use Generated\Shared\Transfer\FilterTransfer;
use Generated\Shared\Transfer\OrderListTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractCustomerController
{
    public const ORDER_LIST_LIMIT = 10;
    public const ORDER_LIST_SORT_FIELD = 'created_at';
    public const ORDER_LIST_SORT_DIRECTION = 'DESC';

    public const PARAM_PAGE = 'page';
    public const DEFAULT_PAGE = 1;

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Spryker\Yves\Kernel\View\View|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction(Request $request)
    {
        $viewData = $this->executeIndexAction($request);

        return $this->view(
            $viewData,
            $this->getFactory()->getCustomerOrderListWidgetPlugins(),
            '@CustomerPage/views/order/order.twig'
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function executeIndexAction(Request $request): array
    {
        $orderListTransfer = $this->createOrderListTransfer($request);

        $orderListTransfer = $this->getFactory()
            ->getSalesClient()
            ->getPaginatedOrder($orderListTransfer);

        $orderList = $orderListTransfer->getOrders();

        return [
            'pagination' => $orderListTransfer->getPagination(),
            'orderList' => $orderList,
        ];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Spryker\Yves\Kernel\View\View|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function detailsAction(Request $request)
    {
        $responseData = $this->getOrderDetailsResponseData($request->query->getInt('id'));

        return $this->view(
            $responseData,
            $this->getFactory()->getCustomerOrderViewWidgetPlugins(),
            '@CustomerPage/views/order-detail/order-detail.twig'
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Generated\Shared\Transfer\OrderListTransfer
     */
    protected function createOrderListTransfer(Request $request)
    {
        $orderListTransfer = new OrderListTransfer();

        $customerTransfer = $this->getLoggedInCustomerTransfer();
        $orderListTransfer->setIdCustomer($customerTransfer->getIdCustomer());

        $filterTransfer = $this->createFilterTransfer();
        $orderListTransfer->setFilter($filterTransfer);

        $paginationTransfer = $this->createPaginationTransfer($request);
        $orderListTransfer->setPagination($paginationTransfer);

        return $orderListTransfer;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Generated\Shared\Transfer\PaginationTransfer
     */
    protected function createPaginationTransfer(Request $request)
    {
        $paginationTransfer = new PaginationTransfer();
        $paginationTransfer->setPage($request->query->getInt(self::PARAM_PAGE, self::DEFAULT_PAGE));
        $paginationTransfer->setMaxPerPage(self::ORDER_LIST_LIMIT);

        return $paginationTransfer;
    }

    /**
     * @return \Generated\Shared\Transfer\FilterTransfer
     */
    protected function createFilterTransfer()
    {
        $filterTransfer = new FilterTransfer();
        $filterTransfer->setOrderBy(self::ORDER_LIST_SORT_FIELD);
        $filterTransfer->setOrderDirection(self::ORDER_LIST_SORT_DIRECTION);

        return $filterTransfer;
    }

    /**
     * @param int $idSalesOrder
     *
     * @return array
     */
    protected function getOrderDetailsResponseData($idSalesOrder)
    {
        $customerTransfer = $this->getLoggedInCustomerTransfer();

        $orderTransfer = new OrderTransfer();
        $orderTransfer
            ->setIdSalesOrder($idSalesOrder)
            ->setFkCustomer($customerTransfer->getIdCustomer());

        $orderTransfer = $this->getFactory()
            ->getSalesClient()
            ->getOrderDetails($orderTransfer);

        $items = $this->getFactory()
            ->getProductBundleClient()
            ->getGroupedBundleItems(
                $orderTransfer->getItems(),
                $orderTransfer->getBundleItems()
            );

        return [
            'order' => $orderTransfer,
            'items' => $items,
        ];
    }
}
