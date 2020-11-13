<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductGraphQl\Model\Resolver;

use Magento\Catalog\Helper\Product\Configuration;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * @inheritdoc
 */
class ConfigurableCartItemOptions implements ResolverInterface
{
    /**
     * Option type name
     */
    private const OPTION_TYPE = 'configurable';

    /**
     * @var Configuration
     */
    private $configurationHelper;

    /** @var Uid */
    private $uidEncoder;

    /**
     * @param Configuration $configurationHelper
     * @param Uid|null $uidEncoder
     */
    public function __construct(
        Configuration $configurationHelper,
        Uid $uidEncoder = null
    ) {
        $this->configurationHelper = $configurationHelper;
        $this->uidEncoder = $uidEncoder ?: ObjectManager::getInstance()
            ->get(Uid::class);
    }

    /**
     * Fetch and format configurable variants.
     *
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Item $cartItem */
        $cartItem = $value['model'];

        $result = [];
        foreach ($this->configurationHelper->getOptions($cartItem) as $option) {
            if (isset($option['option_type'])) {
                //Don't return customizable options in this resolver
                continue;
            }
            $result[] = [
                'id' => $option['option_id'],
                'configurable_product_options_uid' => $this->uidEncoder->encode(
                    self::OPTION_TYPE . '/' . $option['option_id']
                ),
                'option_label' => $option['label'],
                'value_id' => $option['option_value'],
                'configurable_product_options_values_uid' => $this->uidEncoder->encode(
                    self::OPTION_TYPE . '/' . $option['option_id'] . '/' . $option['option_value']
                ),
                'value_label' => $option['value'],
            ];
        }

        return $result;
    }
}
