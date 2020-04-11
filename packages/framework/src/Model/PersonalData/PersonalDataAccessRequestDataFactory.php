<?php

namespace Shopsys\FrameworkBundle\Model\PersonalData;

class PersonalDataAccessRequestDataFactory implements PersonalDataAccessRequestDataFactoryInterface
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\PersonalData\PersonalDataAccessRequestData
     */
    public function create(): PersonalDataAccessRequestData
    {
        return new PersonalDataAccessRequestData();
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\PersonalData\PersonalDataAccessRequestData
     */
    public function createForExport(): PersonalDataAccessRequestData
    {
        $personalDataAccessRequestData = $this->create();
        $personalDataAccessRequestData->type = PersonalDataAccessRequest::TYPE_EXPORT;

        return $personalDataAccessRequestData;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\PersonalData\PersonalDataAccessRequestData
     */
    public function createForDisplay(): PersonalDataAccessRequestData
    {
        $personalDataAccessRequestData = $this->create();
        $personalDataAccessRequestData->type = PersonalDataAccessRequest::TYPE_DISPLAY;

        return $personalDataAccessRequestData;
    }
}
