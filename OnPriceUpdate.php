<?

namespace CalculationPriceSet\Event;

/*
 * обновление цены комплекта (набора)
 */

// 130200.00 цена до изменений

class OnPriceUpdate
{
    function main($ID, &$arFields)
    {
        // получаем комплекты в которых участвует данный товар
        $arSet = $this->GetSet($arFields['PRODUCT_ID']);

        // получаем цены продуктов и id цены комплекта
        $this->GetPrice($arSet);

        // рассчитываем стоимость комплектов 
        $this->CalculationSetPrice($arSet);

        // обновляем цену комплектов
        foreach ($arSet as $key => $value) {
            \Bitrix\Catalog\Model\Price::update($value['PRICE_ID'], ['PRICE' => $value['PRICE']]);
        }
    }

    // ?<--------------------------------------------------------------------------------------------------------------->

    /*
     * получаем комплекты
     */
    function GetSet($productID)
    {
        $arProduct = [];

        $arSelect = ['OWNER_ID'];
        $arFilter = [
            'TYPE' => \CCatalogProductSet::TYPE_SET,
            'ITEM_ID' => $productID,
        ];

        // получаем id комплектов в которых участвует данный товар
        $Element = \CCatalogProductSet::GetList([], $arFilter, false, false, $arSelect);
        while ($product = $Element->Fetch()) {
            $arProduct[] = $product;
        }

        // ?<--------------------------------------------------------------------------------------------------------------->

        // получаем информацию о комплектах
        foreach ($arProduct as $key => &$value) {
            $arSetsByProduct = \CCatalogProductSet::getAllSetsByProduct($value['OWNER_ID'], \CCatalogProductSet::TYPE_SET);
            $value = array_shift($arSetsByProduct);
        }

        return $arProduct;
    }

    // ?<--------------------------------------------------------------------------------------------------------------->

    /*
     * получаем цены продуктов и id цены комплекта
     */
    function GetPrice(&$arSet)
    {
        // собираем id товаров входящие в комплекты
        foreach ($arSet as $key => &$value) {
            // получаем информацию о цене и скидках товара
            $arPrice = \CCatalogProduct::GetOptimalPrice($value['ITEM_ID'], 1, [], 'N', [], 's2');
            // id цены комплекта
            $value['PRICE_ID'] = $arPrice['PRICE']['ID'];

            foreach ($value['ITEMS'] as $key2 => &$value2) {

                // получаем информацию о цене и скидках товара
                $arPrice = \CCatalogProduct::GetOptimalPrice("{$value2['ITEM_ID']}", 1, [], 'N', [], 's2');

                // цена товара с учётом скидки
                $value2['DISCOUNT_PRICE'] = $arPrice['DISCOUNT_PRICE'];
            }
        }
    }

    /*
     * рассчитываем стоимость комплектов 
     */
    function CalculationSetPrice(&$arSet)
    {
        foreach ($arSet as $key => &$set) {
            $set['PRICE'] = 0;

            foreach ($set['ITEMS'] as $key => &$product) {
                $set['PRICE'] += +$product['QUANTITY'] * +$product['DISCOUNT_PRICE'];
            }
        }
    }
}
