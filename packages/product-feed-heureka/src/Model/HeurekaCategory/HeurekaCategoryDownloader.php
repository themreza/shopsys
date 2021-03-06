<?php

namespace Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory;

use SimpleXMLElement;

class HeurekaCategoryDownloader
{
    /**
     * @var string
     */
    protected $heurekaCategoryFeedUrl;

    /**
     * @var \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDataFactoryInterface
     */
    protected $heurekaCategoryDataFactory;

    /**
     * @param string $heurekaCategoryFeedUrl
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDataFactoryInterface $heurekaCategoryDataFactory
     */
    public function __construct(
        $heurekaCategoryFeedUrl,
        HeurekaCategoryDataFactoryInterface $heurekaCategoryDataFactory
    ) {
        $this->heurekaCategoryFeedUrl = $heurekaCategoryFeedUrl;
        $this->heurekaCategoryDataFactory = $heurekaCategoryDataFactory;
    }

    /**
     * @return \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryData[]
     */
    public function getHeurekaCategories()
    {
        $xmlCategoryDataObjects = $this->loadXml()->xpath('/HEUREKA//CATEGORY[CATEGORY_FULLNAME]');

        return $this->convertToHeurekaCategoriesData($xmlCategoryDataObjects);
    }

    /**
     * @return \SimpleXMLElement
     */
    protected function loadXml()
    {
        try {
            return new SimpleXMLElement($this->heurekaCategoryFeedUrl, LIBXML_NOERROR | LIBXML_NOWARNING, true);
        } catch (\Exception $e) {
            throw new \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDownloadFailedException($e);
        }
    }

    /**
     * @param \SimpleXMLElement[] $xmlCategoryDataObjects
     * @return \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryData[]
     */
    protected function convertToHeurekaCategoriesData(array $xmlCategoryDataObjects)
    {
        $heurekaCategoriesData = [];

        foreach ($xmlCategoryDataObjects as $xmlCategoryDataObject) {
            $categoryId = (int)$xmlCategoryDataObject->CATEGORY_ID;

            $heurekaCategoryData = $this->heurekaCategoryDataFactory->create();
            $heurekaCategoryData->id = $categoryId;
            $heurekaCategoryData->name = (string)$xmlCategoryDataObject->CATEGORY_NAME;
            $heurekaCategoryData->fullName = (string)$xmlCategoryDataObject->CATEGORY_FULLNAME;

            $heurekaCategoriesData[$categoryId] = $heurekaCategoryData;
        }

        return $heurekaCategoriesData;
    }
}
