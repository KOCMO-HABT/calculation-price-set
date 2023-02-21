<?

namespace CalculationPriceSet\Event;

/*
 * обновление цены комплекта (набора)
 */

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

        $arProduct = [];

        // собираем id товаров входящие в комплекты
        foreach ($arSet as $key => $value) {
            $arProduct[] = $value['ITEM_ID'];

            foreach ($value['ITEMS'] as $key2 => $value2) {
                $arProduct[] = $value2['ITEM_ID'];
            }
        }

        // ?<--------------------------------------------------------------------------------------------------------------->

        $arProductPrice = [];

        $query = \Bitrix\Catalog\PriceTable::GetList([
            'select' => ['ID', 'PRODUCT_ID', 'PRICE'],
            'filter' => [
                'PRODUCT_ID' => $arProduct,
            ],
        ]);

        // получаем цены товаров
        while ($arFields = $query->Fetch()) {
            $arProductPrice[$arFields['PRODUCT_ID']] = [
                'PRICE' => $arFields['PRICE'],
                'PRICE_ID' => $arFields['ID'],
            ];
        }

        // ?<--------------------------------------------------------------------------------------------------------------->

        // добавляем цены к товарам и id цен к комплектам
        foreach ($arSet as $key => &$value) {
            $value['PRICE_ID'] = $arProductPrice[$value['ITEM_ID']]['PRICE_ID'];

            foreach ($value['ITEMS'] as $key2 => &$value2) {
                $value2['PRICE'] = $arProductPrice[$value2['ITEM_ID']]['PRICE'];
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
                // если у товара не указана скидка
                if ($product['DISCOUNT_PERCENT'] != false) {
                    $product['PRICE'] = + ($product['PRICE'] * (100 - +$product['DISCOUNT_PERCENT'])) / 100;
                }

                $set['PRICE'] += +$product['QUANTITY'] * +$product['PRICE'];
            }
        }
    }
}
