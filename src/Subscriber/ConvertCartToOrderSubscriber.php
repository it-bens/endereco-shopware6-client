<?php

namespace Endereco\Shopware6Client\Subscriber;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Endereco\Shopware6Client\Service\OrderAddressToCustomerAddressDataMatcherInterface;
use Endereco\Shopware6Client\Struct\OrderAddressDataForComparison;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConvertCartToOrderSubscriber implements EventSubscriberInterface
{
    private OrderAddressToCustomerAddressDataMatcherInterface $addressDataMatcher;

    public function __construct(
        OrderAddressToCustomerAddressDataMatcherInterface $orderAddressToCustomerAddressDataMatcher
    ) {
        $this->addressDataMatcher = $orderAddressToCustomerAddressDataMatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'copyEnderecoAddressExtension'
        ];
    }

    public function copyEnderecoAddressExtension(CartConvertedEvent $event): void
    {
        $convertedCart = $event->getConvertedCart();

        if (isset($convertedCart['addresses']) && is_array($convertedCart['addresses'])) {
            foreach ($convertedCart['addresses'] as $key => $address) {
                $this->amendOrderAddressData($address, $event->getCart(), $event->getSalesChannelContext());
                $convertedCart['addresses'][$key] = $address;
            }
        }

        if (isset($convertedCart['deliveries']) && is_array($convertedCart['deliveries'])) {
            foreach ($convertedCart['deliveries'] as $key => $delivery) {
                if (isset($delivery['shippingOrderAddress']) && is_array($delivery['shippingOrderAddress'])) {
                    $shippingOrderAddress = $delivery['shippingOrderAddress'];
                    $this->amendOrderAddressData(
                        $shippingOrderAddress,
                        $event->getCart(),
                        $event->getSalesChannelContext()
                    );
                    $convertedCart['deliveries'][$key]['shippingOrderAddress'] = $shippingOrderAddress;
                }
            }
        }

        $event->setConvertedCart($convertedCart);
    }

    /**
     * @param array<string, mixed> $address
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @return void
     */
    private function amendOrderAddressData(array &$address, Cart $cart, SalesChannelContext $context): void
    {
        $matchingCustomerAddresses = $this->addressDataMatcher->findCustomerAddressesForOrderAddressDataInCart(
            OrderAddressDataForComparison::fromCartToOrderConversionData($address),
            $cart,
            $context
        );

        $matchingCustomerAddresses->filter(static function (CustomerAddressEntity $customerAddressEntity) {
            $customerAddressExtension = $customerAddressEntity->getExtension(
                CustomerAddressExtension::ENDERECO_EXTENSION
            );

            return $customerAddressExtension instanceof EnderecoCustomerAddressExtensionEntity;
        });

        $matchingCustomerAddress = $matchingCustomerAddresses->first();
        if ($matchingCustomerAddress instanceof CustomerAddressEntity) {
            $customerAddressExtension = $matchingCustomerAddress->getExtension(
                CustomerAddressExtension::ENDERECO_EXTENSION
            );

            // This check is logically not necessary because customer address entities without extension were
            // already filtered out. However, type safety demands it.
            if ($customerAddressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
                /** @var string $orderAddressId */
                $orderAddressId = $address['id'];

                $orderAddressExtensionEntity = $customerAddressExtension->createOrderAddressExtension($orderAddressId);
                $orderAddressExtensionData = $orderAddressExtensionEntity->buildCartToOrderConversionData();
                $address[OrderAddressExtension::ENDERECO_EXTENSION] = $orderAddressExtensionData;
            }
        }
    }
}
