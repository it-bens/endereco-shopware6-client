<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Struct\OrderAddressDataForComparison;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\Transformer\AddressTransformer;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class OrderAddressToCustomerAddressDataMatcher implements OrderAddressToCustomerAddressDataMatcherInterface
{
    public function findCustomerAddressesForOrderAddressDataInCart(
        OrderAddressDataForComparison $orderAddressData,
        Cart $cart,
        SalesChannelContext $context
    ): CustomerAddressCollection {
        $customerAddressCollection = new CustomerAddressCollection();

        $orderAddressDataToCompare = $orderAddressData->data();
        array_multisort($orderAddressDataToCompare);

        $customer = $context->getCustomer();
        if ($customer instanceof CustomerEntity) {
            $billingAddress = $customer->getActiveBillingAddress();
            if ($billingAddress instanceof CustomerAddressEntity) {
                $billingAddressDataToCompare = $this->generateCustomerAddressDataToCompare($billingAddress)->data();
                array_multisort($billingAddressDataToCompare);

                if (serialize($orderAddressDataToCompare) === serialize($billingAddressDataToCompare)) {
                    $customerAddressCollection->add($billingAddress);
                }
            }
        }

        foreach ($cart->getDeliveries() as $delivery) {
            $shippingAddress = $delivery->getLocation()->getAddress();
            if ($shippingAddress instanceof CustomerAddressEntity) {
                $shippingAddressDataToCompare = $this->generateCustomerAddressDataToCompare($shippingAddress)->data();
                array_multisort($shippingAddressDataToCompare);

                if (serialize($orderAddressDataToCompare) === serialize($shippingAddressDataToCompare)) {
                    $customerAddressCollection->add($shippingAddress);
                }
            }
        }

        return $customerAddressCollection;
    }

    /**
     * @param CustomerAddressEntity $customerAddressEntity
     * @return OrderAddressDataForComparison
     */
    private function generateCustomerAddressDataToCompare(
        CustomerAddressEntity $customerAddressEntity
    ): OrderAddressDataForComparison {
        /** @var array<string, mixed> $customerAddressDataToCompare */
        $customerAddressDataToCompare = AddressTransformer::transform($customerAddressEntity);

        return OrderAddressDataForComparison::fromCartToOrderConversionData($customerAddressDataToCompare);
    }
}
